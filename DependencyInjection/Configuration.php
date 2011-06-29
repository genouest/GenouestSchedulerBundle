<?php

/*
 * Copyright 2011 Anthony Bretaudeau <abretaud@irisa.fr>
 *
 * Licensed under the CeCILL License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt
 *
 */

namespace Genouest\Bundle\SchedulerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
* SchedulerExtension configuration structure.
*/
class Configuration implements ConfigurationInterface
{
    /**
    * Generates the configuration tree builder.
    *
    * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
    */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('scheduler');

        $rootNode
            ->children()
                ->scalarNode('method')->isRequired()->end()
                ->scalarNode('work_dir')->isRequired()->end()
                ->scalarNode('result_url')->isRequired()->end()
                ->scalarNode('mail_bin')->isRequired()->end()
                ->scalarNode('mail_author_name')->isRequired()->end()
                ->scalarNode('mail_author_address')->isRequired()->end()
                ->scalarNode('drmaa_temp_dir')->end() // This one is only used by drmaa
                ->scalarNode('drmaa_native')->end() // This one is only used by drmaa
            ->end()
        ;

        return $treeBuilder;
    }
}
