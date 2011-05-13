<?php

namespace Genouest\Bundle\SchedulerBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;

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
        foreach ($configs as $config) {
            $this->doConfigLoad($config, $container);
        }
        
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('scheduler.xml');
    }
    
    public function doConfigLoad(array $config, ContainerBuilder $container)
    {
        if (isset($config['shared_dir'])) {
            $container->setParameter('genouest.scheduler.shared_dir', $config['shared_dir']);
        }
        
        if (isset($config['scheduler'])) {
            $container->setParameter('genouest.scheduler.scheduler', $config['scheduler']);
        }
        
        if (isset($config['work_dir'])) {
            $container->setParameter('genouest.scheduler.work_dir', $config['work_dir']);
        }
        else if (isset($config['shared_dir'])) {
            $container->setParameter('genouest.scheduler.work_dir', $config['shared_dir']); // Default work dir is shared dir
        }
        
        if (isset($config['result_url'])) {
            $container->setParameter('genouest.scheduler.result_url', $config['result_url']);
        }
        
        if (isset($config['mail_bin'])) {
            $container->setParameter('genouest.scheduler.mail_bin', $config['mail_bin']);
        }
        
        if (isset($config['mail_author_name'])) {
            $container->setParameter('genouest.scheduler.mail_author_name', $config['mail_author_name']);
        }
        
        if (isset($config['mail_author_address'])) {
            $container->setParameter('genouest.scheduler.mail_author_address', $config['mail_author_address']);
        }
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
