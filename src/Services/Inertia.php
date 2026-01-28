<?php

namespace Creativspeed\InertiaBundle\Services;

class Inertia implements InertiaInterface
{
    /** @var array<string, mixed> */
    private array $sharedProps = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly RequestStack $requestStack,
        private ?string $version = null,
        private readonly string $rootView = 'app',
        private readonly bool $ssrEnabled = false,
        private readonly string $ssrUrl = 'http://127.0.0.1:13714',

    ) {
    }

    public function render(
        string $component,
        array $props = [],
        array $viewData = []
    ): Response {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new \RuntimeException('No request available');
        }

        // Merge shared props with component props
        $allProps = array_merge($this->sharedProps, $props);

        // Build page object
        $page = [
            'component' => $component,
            'props' => $allProps,
            'url' => $request->getRequestUri(),
            'version' => $this->version,
        ];

        // Check if this is an Inertia request
        $isInertiaRequest = $request->headers->get('X-Inertia', false);

        if ($isInertiaRequest) {
            // Return JSON response for Inertia requests
            $response = new JsonResponse($page);
            $response->headers->set('X-Inertia', 'true');
            $response->headers->set('Vary', 'X-Inertia');

            return $response;
        }

        // Render full HTML for first page load
        $html = $this->twig->render("@Inertia/{$this->rootView}.html.twig", array_merge(
            $viewData,
            ['page' => $page]
        ));

        return new Response($html);
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
}
