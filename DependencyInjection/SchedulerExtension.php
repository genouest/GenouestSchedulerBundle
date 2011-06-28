<?php

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
class SchedulerExtension extends Extension
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
        
        
        $container->setParameter('scheduler.mail_author_name', $config['mail_author_name']);
        
        
        $container->setParameter('scheduler.mail_author_address', $config['mail_author_address']);
        
        
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
