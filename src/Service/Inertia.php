<?php

namespace CreativSpeed\InertiaBundle\Service;

use Closure;
use CreativSpeed\InertiaBundle\Contracts\InertiaAuthUserInterface;
use CreativSpeed\InertiaBundle\DTO\Prop;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Environment;

class Inertia implements InertiaInterface, ResetInterface
{
    /** @var array<string, mixed> */
    private array $sharedProps = [];
    private ?string $computedVersion = null;
    private bool $shouldEncryptHistory = false;
    private bool $shouldClearHistory = false;
    private bool $authShared = false;

    public function __construct(
        private readonly Environment $twig,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly string $projectDir,
        private ?string $version = null,
        private readonly string $rootView = 'app',
        private readonly string $rootId = 'app',
        private readonly bool $ssrEnabled = false,
        private readonly string $ssrUrl = 'http://127.0.0.1:13714',
        private readonly ?SerializerInterface $serializer = null,
        private readonly ?HttpClientInterface $httpClient = null,
    ) {
    }

    /**
     * Symfony calls reset() between requests in long-running runtimes
     * (RoadRunner, FrankenPHP worker mode, Swoole). Without this, shared
     * props, the cached asset version, and history flags from one request
     * would leak into the next request served by the same worker process.
     */
    public function reset(): void
    {
        $this->sharedProps = [];
        $this->computedVersion = null;
        $this->shouldEncryptHistory = false;
        $this->shouldClearHistory = false;
        $this->authShared = false;
    }

    public function shareAuthDataIfNeeded(): void
    {
        if ($this->authShared) {
            return;
        }
        $this->authShared = true;

        $user = $this->security->getUser();

        if (!$user) {
            $this->share('auth', ['user' => null]);
            return;
        }

        if ($user instanceof InertiaAuthUserInterface) {
            $userData = $user->getInertiaAuthData();
        } else {
            $userData = [
                'id' => method_exists($user, 'getId') ? $user->getId() : null,
                'identifier' => $user->getUserIdentifier(),
            ];
        }

        $this->share('auth', ['user' => $userData]);
    }

    public function render(string $component, array $props = [], array $viewData = [], ?string $rootView = null): Response
    {
        $this->shareAuthDataIfNeeded();

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('Inertia::render() requires an active request.');
        }

        $allProps = array_merge($this->sharedProps, $props);

        $only = $this->parsePartialHeader($request->headers->get('X-Inertia-Partial-Data', ''));
        $except = $this->parsePartialHeader($request->headers->get('X-Inertia-Partial-Except', ''));
        $partialComponent = $request->headers->get('X-Inertia-Partial-Component');
        $isPartial = $partialComponent === $component && (!empty($only) || !empty($except));

        [$propsToReturn, $deferredGroups, $mergeProps, $deepMergeProps] = $this->resolveProps(
            $allProps,
            $isPartial,
            $only,
            $except,
        );

        $page = [
            'component' => $component,
            'props' => $propsToReturn,
            'url' => $request->getRequestUri(),
            'version' => $this->getVersion(),
        ];

        if (!empty($deferredGroups) && !$isPartial) {
            $page['deferredProps'] = $deferredGroups;
        }
        if (!empty($mergeProps)) {
            $page['mergeProps'] = $mergeProps;
        }
        if (!empty($deepMergeProps)) {
            $page['deepMergeProps'] = $deepMergeProps;
        }
        if ($this->shouldEncryptHistory) {
            $page['encryptHistory'] = true;
        }
        if ($this->shouldClearHistory) {
            $page['clearHistory'] = true;
        }

        if ($request->headers->get('X-Inertia')) {
            return new JsonResponse($page, 200, [
                'X-Inertia' => 'true',
                'Vary' => 'Accept',
                'X-Inertia-Version' => (string) $this->getVersion(),
            ]);
        }

        $view = $rootView ?? $this->rootView;
        $template = str_contains($view, '.html.twig')
            ? $view
            : "@Inertia/{$view}.html.twig";

        if ($this->ssrEnabled && $this->httpClient) {
            $ssr = $this->renderSsr($page);
            if ($ssr !== null) {
                return new Response($this->twig->render($template, array_merge($viewData, [
                    'page' => $page,
                    'ssrBody' => $ssr['body'],
                    'ssrHead' => $ssr['head'],
                ])));
            }
        }

        return new Response($this->twig->render($template, array_merge($viewData, [
            'page' => $page,
        ])));
    }

    /**
     * Walks every prop once, deciding inclusion/exclusion and collecting
     * the metadata (deferred groups, merge/deepMerge key lists) that goes
     * alongside `props` in the page object.
     *
     * @param array<string, mixed> $allProps
     * @param string[] $only
     * @param string[] $except
     * @return array{0: array<string, mixed>, 1: array<string, string[]>, 2: string[], 3: string[]}
     */
    private function resolveProps(array $allProps, bool $isPartial, array $only, array $except): array
    {
        $propsToReturn = [];
        $deferredGroups = [];
        $mergeProps = [];
        $deepMergeProps = [];

        foreach ($allProps as $key => $value) {
            $isProp = $value instanceof Prop;

            // "Always" bypasses both only/except filtering and the
            // lazy/deferred exclusion below — it is, as advertised, always
            // resolved and included.
            if ($isProp && $value->isAlways()) {
                $propsToReturn[$key] = $value();
                $this->collectMergeTag($value, $key, $mergeProps, $deepMergeProps);
                continue;
            }

            if ($isPartial) {
                if (!empty($only) && !in_array($key, $only, true)) {
                    continue;
                }
                if (!empty($except) && in_array($key, $except, true)) {
                    continue;
                }
            } else {
                if ($isProp && $value->isLazy()) {
                    continue;
                }
                if ($isProp && $value->isDeferred()) {
                    $deferredGroups[$value->getGroup()][] = $key;
                    continue;
                }
            }

            $propsToReturn[$key] = $isProp ? $value() : ($value instanceof Closure ? $value() : $value);

            if ($isProp) {
                $this->collectMergeTag($value, $key, $mergeProps, $deepMergeProps);
            }
        }

        return [$propsToReturn, $deferredGroups, $mergeProps, $deepMergeProps];
    }

    /**
     * @param string[] $mergeProps
     * @param string[] $deepMergeProps
     */
    private function collectMergeTag(Prop $prop, string $key, array &$mergeProps, array &$deepMergeProps): void
    {
        if ($prop->isDeepMerge()) {
            $deepMergeProps[] = $key;
        } elseif ($prop->isMerge()) {
            $mergeProps[] = $key;
        }
    }

    /** @return string[] */
    private function parsePartialHeader(string $header): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $header))));
    }

    /**
     * @return array{body: string, head: array<int, string>}|null null on
     *   any failure — SSR is a progressive enhancement, never a hard
     *   requirement; a down or slow SSR server silently falls back to
     *   client-side rendering instead of breaking the page.
     */
    private function renderSsr(array $page): ?array
    {
        try {
            $response = $this->httpClient->request('POST', rtrim($this->ssrUrl, '/') . '/render', [
                'json' => $page,
                'timeout' => 1.0,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $result = $response->toArray();

            return [
                'body' => $result['body'] ?? '',
                'head' => $result['head'] ?? [],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function share(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            $this->sharedProps[$key] = $value;
        }

        return $this;
    }

    public function getShared(): array
    {
        return $this->sharedProps;
    }

    public function version(?string $version): self
    {
        $this->version = $version;
        $this->computedVersion = null; // force re-resolution on the next getVersion() call

        return $this;
    }

    public function getVersion(): ?string
    {
        if ($this->computedVersion !== null) {
            return $this->computedVersion;
        }

        if ($this->version) {
            return $this->computedVersion = $this->version;
        }

        $manifestPaths = [
            $this->projectDir . '/public/build/.vite/manifest.json', // Vite 5+
            $this->projectDir . '/public/build/manifest.json',
        ];

        foreach ($manifestPaths as $path) {
            if (is_file($path)) {
                $hash = md5_file($path);
                return $this->computedVersion = $hash !== false ? $hash : 'dev';
            }
        }

        // No manifest found (dev server, or assets not built yet). A
        // stable string here matters: a random/timestamp value would force
        // a full page reload on every navigation, breaking Vite HMR.
        return $this->computedVersion = 'dev';
    }

    public function lazy(callable $callback): Prop
    {
        return Prop::lazy(Closure::fromCallable($callback));
    }

    public function always(mixed $value): Prop
    {
        return Prop::always($value);
    }

    public function defer(callable $callback, ?string $group = null): Prop
    {
        return Prop::defer(Closure::fromCallable($callback), $group);
    }

    public function merge(mixed $value, array $matchOn = []): Prop
    {
        return Prop::merge($value, $matchOn);
    }

    public function deepMerge(mixed $value, array $matchOn = []): Prop
    {
        return Prop::deepMerge($value, $matchOn);
    }

    public function back(?string $fallback = null): RedirectResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        $referer = $request?->headers->get('referer');

        return new RedirectResponse($referer ?: ($fallback ?? '/'), Response::HTTP_SEE_OTHER);
    }

    public function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    public function location(string $url): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request && $request->headers->get('X-Inertia')) {
            return new Response('', Response::HTTP_CONFLICT, ['X-Inertia-Location' => $url]);
        }

        return new RedirectResponse($url);
    }

    public function redirectWithErrors(FormInterface|array $errors, ?string $to = null): RedirectResponse
    {
        $normalized = is_array($errors) ? $errors : $this->flattenFormErrors($errors);

        $request = $this->requestStack->getCurrentRequest();
        if ($request?->hasSession()) {
            $request->getSession()->getFlashBag()->set('errors', $normalized);
        }

        $target = $to ?? $request?->headers->get('referer') ?? $request?->getUri() ?? '/';

        return new RedirectResponse($target, Response::HTTP_SEE_OTHER);
    }

    /** @return array<string, string> */
    private function flattenFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $origin = $error->getOrigin();
            $path = ($origin && $origin !== $form) ? $origin->getName() : 'global';
            $errors[$path] = $error->getMessage();
        }

        return $errors;
    }

    public function encryptHistory(bool $encrypt = true): self
    {
        $this->shouldEncryptHistory = $encrypt;

        return $this;
    }

    public function clearHistory(): self
    {
        $this->shouldClearHistory = true;

        return $this;
    }

    /**
     * Normalize a complex value (e.g. a Doctrine entity) into a
     * frontend-ready array via the Symfony Serializer, if one is available.
     */
    public function serialize(mixed $data, array $context = ['groups' => ['default']]): mixed
    {
        if (!$this->serializer) {
            return $data;
        }

        return json_decode($this->serializer->serialize($data, 'json', $context), true);
    }
}
