<?php

namespace Creativspeed\InertiaBundle\EventListener;

use HttpException;
use Creativspeed\InertiaBundle\Services\InertiaInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

final class InertiaListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly InertiaInterface $inertia
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
//            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        // Share standard data automatically
        $request = $event->getRequest();
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session) {
            $errors = $session->getFlashBag()->get('errors', []);
            if ($errors) {
                $this->inertia->share('errors', $errors);
            }

            // Share Flash Messages
            $this->inertia->share('flash', [
                'success' => $session->getFlashBag()->get('success', []),
                'error' => $session->getFlashBag()->get('error', []),
            ]);

            // Share Auth User (Example - adjust based on your Security setup)
            // $this->inertia->share('auth', [ 'user' => ... ]);
        }
    }

    /**
     * Handle version mismatch for asset cache busting
     */
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

        // Handle Redirects (302) -> 303 for Inertia
        // Inertia requests expect 303 for redirects after PUT/PATCH/DELETE
        if ($response->getStatusCode() === 302 && in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'])) {
            $response->setStatusCode(Response::HTTP_SEE_OTHER);
        }

        // Version Check (Existing logic)
        $requestVersion = $request->headers->get('X-Inertia-Version');
        $responseVersion = $this->inertia->getVersion(); // Get from service

        if ($requestVersion && $responseVersion && $requestVersion !== $responseVersion) {
            // 409 Conflict logic
            if ($request->hasSession()) {
                $request->getSession()->keep(); // Keep flash data
            }

            $response = new Response('', Response::HTTP_CONFLICT);
            $response->headers->set('X-Inertia-Location', $request->getUri());
            $event->setResponse($response);
        }
    }

    /**
     * Handle redirects for Inertia requests
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpException) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->headers->has('X-Inertia')) {
            return;
        }

        // Handle 302 redirects
        if ($exception->getStatusCode() === Response::HTTP_FOUND) {
            $response = new RedirectResponse(
                $exception->getMessage(),
                Response::HTTP_SEE_OTHER
            );

            $event->setResponse($response);
        }
    }
}
