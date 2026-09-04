<?php

namespace CreativSpeed\InertiaBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class InertiaBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        $container->import('../config/services.php');

        $container->parameters()
            ->set('inertia.version', $config['version'] ?? null)
            ->set('inertia.protocol_version', $config['protocol_version'] ?? 3)
            ->set('inertia.root_view', $config['root_view'] ?? 'app')
            ->set('inertia.root_id', $config['root_id'] ?? 'app')
            ->set('inertia.ssr.enabled', $config['ssr']['enabled'] ?? false)
            ->set('inertia.ssr.url', $config['ssr']['url'] ?? 'http://127.0.0.1:13714')
        ;
    }

    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder,
    ): void {
        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'paths' => [
                    '%kernel.project_dir%/templates/inertia' => 'Inertia',
                ],
            ]);
        }
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
