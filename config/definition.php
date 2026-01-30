<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('version')->info('Asset version hash')->defaultNull()->end()
            ->scalarNode('root_view')->defaultValue('app')->end()
            ->arrayNode('ssr')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')->defaultFalse()->end()
                    ->scalarNode('url')->defaultValue('http://127.0.0.1:13714')->end()
                ->end()
            ->end()
        ->end();
};
