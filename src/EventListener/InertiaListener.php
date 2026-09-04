<?php

namespace CreativSpeed\InertiaBundle\EventListener;

use CreativSpeed\InertiaBundle\Service\InertiaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Wires the two pieces of the Inertia protocol that have to happen outside
 * a controller: sharing per-request data (flash messages, the current
 * auth user) before the controller runs, and fixing up the response after
 * it runs (redirect status codes, asset-version conflicts).
 */
final class InertiaListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly InertiaInterface $inertia,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->inertia->shareAuthDataIfNeeded();

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        // Share every flash message type generically — not just a
        // hardcoded success/error pair — so any category an app sets
        // (warning, info, ...) actually reaches the frontend. `errors` is
        // pulled out separately since Inertia's convention is a dedicated
        // top-level `errors` prop, not a flash sub-key.
        $flashes = $session->getFlashBag()->all();

        if (isset($flashes['errors'])) {
            $this->inertia->share('errors', $flashes['errors']);
            unset($flashes['errors']);
        }

        if (!empty($flashes)) {
            $this->inertia->share('flash', $flashes);
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$request->headers->has('X-Inertia')) {
            return;
        }

        // Inertia requires a 303 (not 302) after PUT/PATCH/DELETE —
        // otherwise the browser/XHR resends the redirect target with the
        // original method instead of downgrading to GET.
        if ($response->getStatusCode() === 302 && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)) {
            $response->setStatusCode(Response::HTTP_SEE_OTHER);
        }

        // Asset-version conflicts only matter on GET navigations. Checking
        if (!$request->isMethod('GET')) {
            return;
        }

        $requestVersion = $request->headers->get('X-Inertia-Version');
        $responseVersion = $this->inertia->getVersion();

        if ($requestVersion !== null && $responseVersion !== null && $requestVersion !== $responseVersion) {
            if ($request->hasSession()) {
                // Re-flash anything already consumed this request (e.g. by
                // onKernelController above) so it survives the full page
                // reload the client is about to perform.
                $request->getSession()->getFlashBag()->keep();
            }

            $conflict = new Response('', Response::HTTP_CONFLICT);
            $conflict->headers->set('X-Inertia-Location', $request->getUri());
            $event->setResponse($conflict);
        }
    }
}
