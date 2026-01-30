<?php

use Creativspeed\InertiaBundle\EventListener\InertiaListener;
use Creativspeed\InertiaBundle\Services\Inertia;
use Creativspeed\InertiaBundle\Services\InertiaInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function(ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Register the Inertia service
    $services->set(InertiaInterface::class, Inertia::class)
        ->arg('$version', param('inertia.version'))
        ->arg('$rootView', param('inertia.root_view'))
        ->arg('$ssrEnabled', param('inertia.ssr.enabled'))
        ->arg('$ssrUrl', param('inertia.ssr.url'))
        ->public();

    // Alias for easier access
    $services->alias(Inertia::class, InertiaInterface::class);

    // Register event listener
    $services->set(InertiaListener::class)
        ->tag('kernel.event_subscriber')
    ;

};
