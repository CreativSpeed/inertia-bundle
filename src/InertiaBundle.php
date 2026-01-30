<?php

namespace Creativspeed\InertiaBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class InertiaBundle extends AbstractBundle
{

    /**
     * Configure bundle semantic configuration
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        // Import configuration definition from external file
        $definition->import('../config/definition.php');
    }

    /**
     * Load services and configuration
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // Load service definitions
        $container->import('../config/services.php');

        // Set parameters from configuration
        $container->parameters()
            ->set('inertia.version', $config['version'] ?? null)
            ->set('inertia.root_view', $config['root_view'] ?? 'app')
            ->set('inertia.ssr.enabled', $config['ssr']['enabled'] ?? false)
            ->set('inertia.ssr.url', $config['ssr']['url'] ?? 'http://127.0.0.1:13714')
        ;
    }

    /**
     * Prepend configuration to other bundles
     */
    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // Prepend Twig configuration for Inertia
        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'paths' => [
                    '%kernel.project_dir%/templates/inertia' => 'Inertia',
                ],
            ]);
        }
    }

    /**
     * Get bundle path (for modern structure)
     *
     * This is automatically handled by AbstractBundle,
     * but you can override if needed for custom paths
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

}
