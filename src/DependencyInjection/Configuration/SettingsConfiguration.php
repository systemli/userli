<?php

declare(strict_types=1);

namespace App\DependencyInjection\Configuration;

use Override;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SettingsConfiguration implements ConfigurationInterface
{
    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    #[Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('settings');

        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();
        $root
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->enumNode('type')
                        ->values(['string', 'integer', 'boolean', 'array', 'float', 'email', 'url', 'password', 'textarea'])
                        ->defaultValue('string')
                    ->end()
                    ->scalarNode('default')->defaultNull()->end()
                    ->arrayNode('validation')
                        ->addDefaultsIfNotSet()
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
