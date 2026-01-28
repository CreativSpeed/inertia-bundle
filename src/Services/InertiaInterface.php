<?php

namespace Creativspeed\InertiaBundle\Services;

interface InertiaInterface
{
    /**
     * Render an Inertia response
     *
     * @param string $component Component name
     * @param array<string, mixed> $props Component props
     * @param array<string, mixed> $viewData Additional view data
     */
    public function render(
        string $component,
        array $props = [],
        array $viewData = []
    ): Response;

    /**
     * Share data with all Inertia responses
     *
     * @param string|array<string, mixed> $key
     * @param mixed $value
     */
    public function share(string|array $key, mixed $value = null): self;

    /**
     * Get shared data
     *
     * @return array<string, mixed>
     */
    public function getShared(): array;

    /**
     * Set asset version
     */
    public function version(?string $version): self;

    /**
     * Get asset version
     */
    public function getVersion(): ?string;
}
