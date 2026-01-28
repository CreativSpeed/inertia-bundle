<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('version')->info('Asset version for cache busting')->defaultNull()->end()
            ->scalarNode('root_view')->info('Root Twig template for Inertia responses')->defaultValue('app')->end()
            ->arrayNode('ssr')->info('Server-side rendering configuration')->addDefaultsIfNotSet()->children()->booleanNode('enabled')->info('Enable server-side rendering')->defaultFalse()->end()
            ->scalarNode('url')->info('SSR server URL')->defaultValue('http://127.0.0.1:13714')->end()
        ->end()
        ->end()
        ->end();
};
