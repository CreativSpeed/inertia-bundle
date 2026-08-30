<?php

namespace CreativSpeed\InertiaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Form\FormInterface;

readonly class InertiaFormValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(private RequestStack $requestStack) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->headers->has('X-Inertia')) {
            return;
        }

        $form = $request->attributes->get('inertia_form');

        if ($form instanceof FormInterface && $form->isSubmitted() && !$form->isValid()) {
            $errors = [];

            foreach ($form->getErrors(true) as $error) {
                $path = $error->getOrigin()?->getName() ?? 'global';
                $errors[$path] = $error->getMessage();
            }

            $this->requestStack->getSession()->getFlashBag()->set('errors', $errors);

            $referer = $request->headers->get('referer', $request->getUri());
            $event->setResponse(new RedirectResponse($referer, 303));
        }
    }
}