<?php

namespace RavuAlHemio\TotstrichBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $objTreeBuilder = new TreeBuilder();
        $objRootNode = $objTreeBuilder->root('totstrich');

        $objRootNode
            ->children()
                ->integerNode('deadlines_per_page')
                    ->min(1)
                    ->defaultValue(32)
                ->end()
                ->scalarNode('date_format')
                    ->defaultValue('Y-m-d H:i:s')
                ->end()
            ->end()
        ;
    }
}
