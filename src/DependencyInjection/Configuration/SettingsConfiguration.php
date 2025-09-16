<?php

declare(strict_types=1);

namespace App\DependencyInjection\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SettingsConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('settings');

        $treeBuilder->getRootNode()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->enumNode('type')
                        ->values(['string', 'integer', 'boolean', 'array', 'float', 'email', 'url', 'password', 'textarea'])
                        ->defaultValue('string')
                    ->end()
                    ->scalarNode('default')->defaultNull()->end()
                    ->arrayNode('validation')
                        ->children()
                            ->scalarNode('regex')->defaultNull()->end()
                            ->integerNode('min_length')->defaultNull()->end()
                            ->integerNode('max_length')->defaultNull()->end()
                            ->integerNode('min')->defaultNull()->end()
                            ->integerNode('max')->defaultNull()->end()
                            ->arrayNode('choices')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
