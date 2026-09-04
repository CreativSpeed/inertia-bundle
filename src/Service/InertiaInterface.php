<?php

namespace CreativSpeed\InertiaBundle\Service;

use CreativSpeed\InertiaBundle\DTO\Prop;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

interface InertiaInterface
{
    /**
     * Render an Inertia response.
     *
     * @param array<string, mixed> $props
     * @param array<string, mixed> $viewData Extra variables passed to the root Twig template.
     * @param string|null $rootView Override the configured root_view for this render only
     *   (e.g. render an admin section through templates/inertia/admin.html.twig).
     */
    public function render(string $component, array $props = [], array $viewData = [], ?string $rootView = null): Response;

    /**
     * Share data with every Inertia response for the rest of this request.
     *
     * @param string|array<string, mixed> $key
     */
    public function share(string|array $key, mixed $value = null): self;

    /** @return array<string, mixed> */
    public function getShared(): array;

    /** Override the asset version for the rest of this request. */
    public function version(?string $version): self;

    /** The current asset version — explicit, or auto-detected from the Vite manifest. */
    public function getVersion(): ?string;

    /** Only resolved and included when explicitly requested via a partial reload. */
    public function lazy(callable $callback): Prop;

    /** Always included, even on a partial reload that would otherwise exclude it. */
    public function always(mixed $value): Prop;

    /** Excluded from the initial load; fetched by the client in a follow-up request. */
    public function defer(callable $callback, ?string $group = null): Prop;

    /** The client merges this value into the existing one instead of replacing it. */
    public function merge(mixed $value, array $matchOn = []): Prop;

    /** Like merge(), but merges nested arrays/objects recursively. */
    public function deepMerge(mixed $value, array $matchOn = []): Prop;

    /** Redirect back to the referring page (or $fallback), as a 303. */
    public function back(?string $fallback = null): RedirectResponse;

    /** Redirect to $url. PUT/PATCH/DELETE requests are auto-converted to 303 by the response listener. */
    public function redirect(string $url, int $status = 302): RedirectResponse;

    /**
     * Force a full browser navigation to $url (e.g. an external site),
     * via a 409 + X-Inertia-Location on Inertia XHR requests.
     */
    public function location(string $url): Response;

    /**
     * Redirect back with form errors flashed for the next Inertia response's
     * shared `errors` prop to pick up.
     *
     * @param FormInterface|array<string, string> $errors
     */
    public function redirectWithErrors(FormInterface|array $errors, ?string $to = null): RedirectResponse;

    /**
     * Encrypt this page's history state client-side — for pages carrying
     * sensitive data you don't want recoverable from the browser's
     * back/forward cache.
     */
    public function encryptHistory(bool $encrypt = true): self;

    /** Instruct the client to clear all encrypted history state (e.g. on logout). */
    public function clearHistory(): self;

    /**
     * @internal Called by InertiaListener on kernel.controller; not meant
     * to be called from application code.
     */
    public function shareAuthDataIfNeeded(): void;
}
