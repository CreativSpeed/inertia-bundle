<?php

namespace Creativspeed\InertiaBundle\Services;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Creativspeed\InertiaBundle\DTO\Prop;

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

    public function lazy(callable $callback): Prop;
    public function always(mixed $value): Prop;
    public function defer(callable $callback, ?string $group = null): Prop;
}
