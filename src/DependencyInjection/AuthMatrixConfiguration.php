<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class AuthMatrixConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('auth_matrix');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('entries')
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('prefix')->end()
                        ->arrayNode('roles')
                            ->scalarPrototype()->end()
                        ->end()
                        ->arrayNode('permissions')
                            ->children()
                                ->scalarNode('create')->defaultFalse()->end()
                                ->scalarNode('read')->defaultFalse()->end()
                                ->scalarNode('update')->defaultFalse()->end()
                                ->scalarNode('delete')->defaultFalse()->end()
                                ->scalarNode('execute')->defaultFalse()->end()
                                ->scalarNode('administration')->defaultFalse()->end()
                            ->end()
                        ->end()
                        ->arrayNode('filters')
                            ->children()
                                ->scalarNode('organisation_only')->defaultTrue()->end()
                                ->scalarNode('published_dossiers')->defaultFalse()->end()
                                ->scalarNode('unpublished_dossiers')->defaultFalse()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
