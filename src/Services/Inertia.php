<?php

namespace Creativspeed\InertiaBundle\Services;

use Closure;
use Twig\Environment;
use Creativspeed\InertiaBundle\Contracts\InertiaAuthUserInterface;
use Creativspeed\InertiaBundle\DTO\Prop;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class Inertia implements InertiaInterface
{
    /** @var array<string, mixed> */
    private array $sharedProps = [];
    private ?string $computedVersion = null;

    public function __construct(
        private readonly Environment $twig,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private ?string $version = null,
        private readonly string $rootView = 'app',
        private readonly bool $ssrEnabled = false,
        private readonly string $ssrUrl = 'http://127.0.0.1:13714',
        private readonly ?SerializerInterface $serializer = null,
    ) {
        $this->shareAuthData();
    }

    private function shareAuthData(): void
    {
        // Ensure Security is injected (it might be optional in your bundle)
        if (!isset($this->security)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user) {
            $this->share('auth', ['user' => null]);
            return;
        }

        // 1. Check if the User entity knows how to format itself
        if ($user instanceof InertiaAuthUserInterface) {
            $userData = $user->getInertiaAuthData();
        }
        // 2. Fallback: If they don't implement the interface, send minimal data safely
        else {
            $userData = [
                'id' => method_exists($user, 'getId') ? $user->getId() : null,
                'identifier' => $user->getUserIdentifier(), // Always exists in Symfony UserInterface
            ];
        }

        $this->share('auth', ['user' => $userData]);

    }

    public function render(string $component, array $props = [], array $viewData = []): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new \RuntimeException('No request available');
        }

        // 1. Merge Shared Props
        $allProps = array_merge($this->sharedProps, $props);

        // 2. Handle Partial Reloads & Lazy Evaluation
        $only = array_filter(explode(',', $request->headers->get('X-Inertia-Partial-Data', '')));
        $except = array_filter(explode(',', $request->headers->get('X-Inertia-Partial-Except', '')));
        $partialComponent = $request->headers->get('X-Inertia-Partial-Component');

        $isPartial = $partialComponent === $component && (!empty($only) || !empty($except));

        $propsToReturn = [];

        foreach ($allProps as $key => $value) {
            // Logic for "Always" props
            if ($value instanceof Prop && $value->isAlways()) {
                $propsToReturn[$key] = $value();
                continue;
            }

            // Logic for Partial Reloads
            if ($isPartial) {
                if (!empty($only) && !in_array($key, $only)) {
                    continue; // Skip if not in 'only'
                }
                if (!empty($except) && in_array($key, $except)) {
                    continue; // Skip if in 'except'
                }
            } else {
                // If not partial, skip "Lazy" props
                if ($value instanceof Prop && $value->isLazy()) {
                    continue;
                }
            }

            // Resolve value
            $propsToReturn[$key] = ($value instanceof Prop) ? $value() : ($value instanceof \Closure ? $value() : $value);
        }

        $page = [
            'component' => $component,
            'props' => $propsToReturn,
            'url' => $request->getRequestUri(),
            'version' => $this->version,
        ];

        // 3. Return JSON for Inertia Requests
        if ($request->headers->get('X-Inertia')) {
            return new JsonResponse($page, 200, [
                'X-Inertia' => 'true',
                'Vary' => 'Accept',
                'X-Inertia-Version' => $this->version // Ensure version is sent back
            ]);
        }

        // 4. Server-Side Rendering (SSR)
        if ($this->ssrEnabled && $this->httpClient) {
            try {
                $response = $this->httpClient->request('POST', $this->ssrUrl . '/render', [
                    'json' => $page,
                    'timeout' => 1.0, // Fail fast if SSR is down
                ]);

                if ($response->getStatusCode() === 200) {
                    $result = $response->toArray();
                    return new Response($result['body']); // Return pre-rendered HTML
                }
            } catch (\Exception $e) {
                // Fallback to CSR (Client Side Rendering) silently or log warning
            }
        }

        $view = $this->rootView; // "app" by default, or "base"
        $template = str_contains($view, '.html.twig') ? $view : "@Inertia/{$view}.html.twig";

        // 5. Fallback: Client-Side Rendering (Twig)
        return new Response($this->twig->render($template, array_merge(
            $viewData,
            ['page' => $page] // Pass page object to view for data-page attribute
        )));
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

        return $this;
    }

    public function getVersion(): ?string
    {

        // 1. Return cached result if we already computed it this request
        if ($this->computedVersion) {
            return $this->computedVersion;
        }

        // 2. If user manually set a version in config, use it
        if ($this->version) {
            return $this->computedVersion = $this->version;
        }

        // 3. Define possible locations for Vite's manifest
        $projectDir = $this->kernel->getProjectDir();
        $manifestPaths = [
            // Vite 5+ (inside .vite folder)
            $projectDir . '/public/build/.vite/manifest.json',
            // Standard location
            $projectDir . '/public/build/manifest.json',
        ];

        // 4. Check for manifest file
        foreach ($manifestPaths as $path) {
            if (file_exists($path)) {
                // In production, hash the manifest file to detect changes
                return $this->computedVersion = md5_file($path);
            }
        }

        // 5. Fallback for Development
        // In dev, we return a static string. If we returned a random hash
        // or timestamp here, Inertia would force a full page reload on every
        // navigation, breaking Vite's Hot Module Replacement (HMR).
        return $this->computedVersion = 'dev';
    }

    public function lazy(callable $callback): Prop
    {
        return Prop::lazy($callback(...));
    }

    public function always(mixed $value): Prop
    {
        return Prop::always($value);
    }

    public function defer(callable $callback, ?string $group = null): Prop
    {
        return Prop::defer($callback(...), $group);
    }

    /**
     * Automatically normalize complex entities into frontend-ready arrays.
     */
    public function serialize(mixed $data, array $context = ['groups' => ['default']]): mixed
    {
        if (!$this->serializer) {
            return $data;
        }

        // Serialize to JSON -> raw array for Inertia response
        return json_decode($this->serializer->serialize($data, 'json', $context), true);
    }

    private function detectVersion(): string
    {
        if ($this->version) {
            return $this->version;
        }

        $manifestPath = $this->projectDir . '/public/build/manifest.json';
        if (file_exists($manifestPath)) {
            return md5_file($manifestPath);
        }

        // Fallback
        return '1.0';
    }
}
