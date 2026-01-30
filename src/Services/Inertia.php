<?php

namespace Creativspeed\InertiaBundle\Services;

use Creativspeed\InertiaBundle\DTO\Prop;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class Inertia implements InertiaInterface
{
    /** @var array<string, mixed> */
    private array $sharedProps = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private ?string $version = null,
        private readonly string $rootView = 'app',
        private readonly bool $ssrEnabled = false,
        private readonly string $ssrUrl = 'http://127.0.0.1:13714',
    ) {
        $this->shareAuthData();
    }

    private function shareAuthData(): void
    {
        $user = $this->security->getUser();

        $this->share('auth', [
            'user' => $user ? [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ] : null,
        ]);
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

        // 5. Fallback: Client-Side Rendering (Twig)
        return new Response($this->twig->render("@Inertia/{$this->rootView}.html.twig", array_merge(
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
        return $this->version;
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
}
