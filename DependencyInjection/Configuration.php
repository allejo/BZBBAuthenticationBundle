<?php

namespace allejo\BZBBAuthenticationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('bzbb_authentication');

        $rootNode
            ->children()
                ->scalarNode('user_class')->cannotBeEmpty()->end()
                ->arrayNode('routes')
                    ->children()
                        ->scalarNode('login_route')->cannotBeEmpty()->end()
                        ->scalarNode('success_route')->cannotBeEmpty()->end()
                    ->end()
                ->end() // routes
                ->arrayNode('groups')
                    ->treatNullLike(array())
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
