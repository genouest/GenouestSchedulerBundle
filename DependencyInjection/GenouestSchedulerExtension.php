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

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * SchedulerExtension is an extension for the Scheduler bundle.
 */
class GenouestSchedulerExtension extends Extension
{
    /**
     * Loads the Scheduler configuration.
     *
     * @param array            $config    An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        
        $shortcutsMethod = array('drmaa', 'local');
        if (in_array($config['method'], $shortcutsMethod)) {
            $container->setParameter('scheduler.method.class', '%scheduler.method.'.$config['method'].'.class%');
        }
        else
            $container->setParameter('scheduler.method.class', $config['method']); // Full class given
        
        
        $container->setParameter('scheduler.work_dir', $config['work_dir']);
        
        
        $container->setParameter('scheduler.result_url', $config['result_url']);
        
        
        $container->setParameter('scheduler.mail_bin', $config['mail_bin']);
        
        
        $container->setParameter('scheduler.from_email', array_slice($config['from_email'], 0, 1)); // Only the first address will be used
        
        
        $container->setParameter('scheduler.history_length', $config['history_length']);
        
        // drmaa specific options
        $container->setParameter('scheduler.drmaa_temp_dir', $config['work_dir']); // Default temp dir is work dir
        if (isset($config['drmaa_temp_dir'])) {
            $container->setParameter('scheduler.drmaa_temp_dir', $config['drmaa_temp_dir']);
        }
        
        if (isset($config['drmaa_native'])) {
            $container->setParameter('scheduler.drmaa_native', $config['drmaa_native']);
        }
        
        
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('scheduler.xml');
    }

    /**
     * Returns the base path for the XSD files.
     *
     * @return string The XSD base path
     */
    public function getXsdValidationBasePath()
    {
        return __DIR__.'/../Resources/config/schema';
    }

    /**
     * Returns the namespace to be used for this extension (XML namespace).
     *
     * @return string The XML namespace
     */
    public function getNamespace()
    {
        return 'http://www.genouest.org/schema/scheduler';
    }
}
