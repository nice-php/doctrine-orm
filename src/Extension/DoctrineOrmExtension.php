<?php

/*
 * Copyright (c) Tyler Sommer
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Nice\Extension;

use Doctrine\Common\Proxy\AbstractProxyFactory;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineOrmExtension extends Extension
{
    /**
     * @var array
     */
    private $options = array();

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }
    
    /**
     * Returns extension configuration
     *
     * @param array            $config    An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @return DoctrineOrmConfiguration
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new DoctrineOrmConfiguration();
    }
    
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs    An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configs[] = $this->options;
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->register('doctrine.orm.metadata_driver', 'Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain');

        foreach ($config['mapping'] as $name => $mappingConfig) {
            $mappingConfig['name'] = $name;
            $this->configureMappingDriver($mappingConfig, $container);
        }

        $container->register('doctrine.orm.configuration', 'Doctrine\ORM\Configuration')
            ->addMethodCall('setMetadataDriverImpl', array(new Reference('doctrine.orm.metadata_driver')))
            ->addMethodCall('setProxyNamespace', array('Proxy'));

        if ($container->hasParameter('app.cache') && $container->getParameter('app.cache') === true) {
            $container->getDefinition('doctrine.orm.configuration')
                ->addMethodCall('setProxyDir', array('%app.cache_dir%/doctrine'));
        } else {
            $container->getDefinition('doctrine.orm.configuration')
                ->addMethodCall('setProxyDir', array(sys_get_temp_dir().'/doctrine'))
                ->addMethodCall('setAutoGenerateProxyClasses', array(AbstractProxyFactory::AUTOGENERATE_EVAL));

        }

        $container->register('doctrine.orm.entity_manager', 'Doctrine\ORM\EntityManager')
            ->setFactoryClass('Doctrine\ORM\EntityManager')
            ->setFactoryMethod('create')
            ->addArgument($config['database'])
            ->addArgument(new Reference('doctrine.orm.configuration'));

        $container->setAlias('doctrine.dbal.configuration', new Alias('doctrine.orm.configuration'));

        $container->register('doctrine.dbal.database_connection', 'Doctrine\DBAL\Connection')
            ->setFactoryService('doctrine.orm.entity_manager')
            ->setFactoryMethod('getConnection');
    }

    private function configureMappingDriver(array $config, ContainerBuilder $container)
    {
        $name = 'doctrine.orm.metadata.'.$config['name'];
        switch ($config['driver']) {
            case 'annotation':
                $container->register($name, 'Doctrine\ORM\Mapping\Driver\AnnotationDriver')
                    ->setPublic(false)
                    ->setFactoryService('doctrine.orm.configuration')
                    ->setFactoryMethod('newDefaultAnnotationDriver')
                    ->addArgument($config['paths'])
                    ->addArgument(false);

                break;

            case 'xml':
                $container->register($name, 'Doctrine\ORM\Mapping\Driver\XmlDriver')
                    ->setPublic(false)
                    ->addArgument($config['paths']);

                break;

            case 'yml':
                $container->register($name, 'Doctrine\ORM\Mapping\Driver\YamlDriver')
                    ->setPublic(false)
                    ->addArgument($config['paths']);

                break;

            case 'php':
                $container->register($name, 'Doctrine\Common\Persistence\Mapping\Driver\PHPDriver')
                    ->setPublic(false)
                    ->addArgument($config['paths']);

                break;
        }

        $container->getDefinition('doctrine.orm.metadata_driver')
            ->addMethodCall('addDriver', array(new Reference($name), $config['namespace']));
    }
}
