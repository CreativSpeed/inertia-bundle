<?php

namespace Creativspeed\InertiaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class InertiaListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
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

        // Check for Inertia request
        if (!$request->headers->has('X-Inertia')) {
            return;
        }

        // Check for version mismatch
        $requestVersion = $request->headers->get('X-Inertia-Version');
        $responseVersion = $response->headers->get('X-Inertia-Version');

        if ($requestVersion && $responseVersion && $requestVersion !== $responseVersion) {
            // Force reload on version mismatch
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
