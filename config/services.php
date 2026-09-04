<?php

use CreativSpeed\InertiaBundle\EventListener\InertiaListener;
use CreativSpeed\InertiaBundle\Service\Inertia;
use CreativSpeed\InertiaBundle\Service\InertiaInterface;
use CreativSpeed\InertiaBundle\Twig\InertiaExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Registered under the concrete class, aliased from the interface —
    // the conventional direction for Symfony DI (autowiring by either
    // Inertia::class or InertiaInterface::class both resolve the same
    // single instance).
    $services->set(Inertia::class)
        ->arg('$projectDir', param('kernel.project_dir'))
        ->arg('$version', param('inertia.version'))
        ->arg('$rootView', param('inertia.root_view'))
        ->arg('$rootId', param('inertia.root_id'))
        ->arg('$ssrEnabled', param('inertia.ssr.enabled'))
        ->arg('$ssrUrl', param('inertia.ssr.url'))
        ->public();

    $services->alias(InertiaInterface::class, Inertia::class)->public();

    $services->set(InertiaListener::class)
        ->tag('kernel.event_subscriber');

    $services->set(InertiaExtension::class)
        ->arg('$inertiaVersion', param('inertia.protocol_version'))
        ->arg('$rootId', param('inertia.root_id'))
        ->tag('twig.extension');
};
