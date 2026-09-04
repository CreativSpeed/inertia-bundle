<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('version')
                ->info('Asset version hash. Leave null to auto-detect from the Vite manifest.')
                ->defaultNull()
            ->end()
            ->integerNode('protocol_version')
                ->info('3 renders <script data-page type="application/json">; 2 renders the legacy <div data-page="...">.')
                ->defaultValue(3)
            ->end()
            ->scalarNode('root_view')->defaultValue('app')->end()
            ->scalarNode('root_id')
                ->info('The id of the DOM element Inertia mounts into.')
                ->defaultValue('app')
            ->end()
            ->arrayNode('ssr')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')->defaultFalse()->end()
                    ->scalarNode('url')->defaultValue('http://127.0.0.1:13714')->end()
                ->end()
            ->end()
        ->end();
};
