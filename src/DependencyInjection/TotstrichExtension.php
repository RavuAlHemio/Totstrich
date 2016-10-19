<?php

namespace RavuAlHemio\TotstrichBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;


class TotstrichExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $objConfig = new Configuration();
        $objProcessedConfig = $this->processConfiguration($objConfig, $configs);

        $container->setParameter('totstrich.deadlines_per_page', $objProcessedConfig['deadlines_per_page']);
        $container->setParameter('totstrich.date_format', $objProcessedConfig['date_format']);
    }
}
