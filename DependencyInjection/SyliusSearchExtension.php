<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\SearchBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Sylius\Bundle\ResourceBundle\DependencyInjection\AbstractResourceExtension;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Driver\DatabaseDriverFactory;

/**
 * Class SyliusSearchExtension
 *
 * @author Argyrios Gounaris <agounaris@gmail.com>
 */
class SyliusSearchExtension extends AbstractResourceExtension
{

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $config);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $classes = isset($config['classes']) ? $config['classes'] : array();
        $this->createResourceServices($classes, $container);

        $container->setParameter('sylius_search.config', $config);

        $commandClass = sprintf('sylius.search.%s.indexer', ucfirst($config['driver']));
        $container->setAlias('sylius_search.command.indexer', $commandClass);

        $finderClass = sprintf('sylius_search.%s.finder', ucfirst($config['driver']));
        $container->setAlias('sylius_search.finder', $finderClass);

        $queryLoggerClass = sprintf('sylius_search.%s.query.logger', ucfirst($config['query_logger']['driver']));
        $container->setAlias('sylius_search.query.logger', $queryLoggerClass);
        $container->setParameter('sylius_search.query.logger.enabled', $config['query_logger']['enabled']);

        $container->setParameter('sylius_search.request.method', $config['request_method']);
        $container->setParameter('sylius_search.search.template', $config['search_form_template']);
        $container->setParameter('sylius_search.pre_search_filter.enabled', $config['filters']['pre_search_filter']['enabled']);
        $container->setParameter('sylius_search.pre_search_filter.taxon', $config['filters']['pre_search_filter']['taxonomy']);

        $container->setParameter('sylius_search.custom.accessors', $config['custom_accessors']);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    private function createResourceServices(array $config, ContainerBuilder $container)
    {
        list($prefix, $resourceName) = explode('_', $this->getName());

        DatabaseDriverFactory::get(
            $config['driver'],
            $container,
            $prefix,
            $resourceName,
            array_key_exists('templates', $config) ? $config['templates'] : null
        )->load($config);
    }

    /**
     * @return string
     */
    private function getName()
    {
        return 'sylius_search';
    }

}