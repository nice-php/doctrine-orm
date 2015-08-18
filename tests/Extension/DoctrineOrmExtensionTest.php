<?php

/*
 * Copyright (c) Tyler Sommer
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Nice\Tests\Extension;

use Nice\Extension\DoctrineOrmExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineOrmExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test the DoctrineOrmExtension
     */
    public function testConfigureWithoutCache()
    {
        $extension = new DoctrineOrmExtension();

        $container = new ContainerBuilder();
        $extension->load(array(
            'doctrine' => array(
                'database' => array(
                    'driver' => 'pdo_mysql'
                ),
                'mapping' => array(
                    'default' => array(
                        'namespace' => 'Example',
                        'paths' => array(
                            __DIR__
                        )
                    )
                )
            )
        ), $container);

        $this->assertTrue($container->has('doctrine.orm.entity_manager'));
        $this->assertTrue($container->has('doctrine.orm.configuration'));
        $this->assertTrue($container->has('doctrine.dbal.database_connection'));
        $this->assertTrue($container->has('doctrine.dbal.configuration'));
        $this->assertCount(4, $container->getDefinition('doctrine.orm.configuration')->getMethodCalls());
    }

    /**
     * Test different drivers
     */
    public function testDifferentDrivers()
    {
        $extension = new DoctrineOrmExtension();

        $container = new ContainerBuilder();
        $extension->load(array(
            'doctrine' => array(
                'database' => array(
                    'driver' => 'pdo_mysql'
                ),
                'mapping' => array(
                    'xml' => array(
                        'driver' => 'xml',
                        'namespace' => 'Example\Xml',
                        'paths' => array(
                            __DIR__
                        )
                    ),
                    'yaml' => array(
                        'driver' => 'yml',
                        'namespace' => 'Example\Yaml',
                        'paths' => array(
                            __DIR__
                        )
                    ),
                    'php' => array(
                        'driver' => 'php',
                        'namespace' => 'Example\Php',
                        'paths' => array(
                            __DIR__
                        )
                    )
                )
            )
        ), $container);

        $this->assertTrue($container->has('doctrine.orm.metadata.xml'));
        $this->assertTrue($container->has('doctrine.orm.metadata.yaml'));
        $this->assertTrue($container->has('doctrine.orm.metadata.php'));
        $this->assertCount(3, $container->getDefinition('doctrine.orm.metadata_driver')->getMethodCalls());
    }

    /**
     * Test the DoctrineOrmExtension
     */
    public function testConfigureWithCache()
    {
        $extension = new DoctrineOrmExtension();

        $container = new ContainerBuilder();
        $container->setParameter('app.cache', true);
        $extension->load(array(
            'doctrine' => array(
                'database' => array(
                    'driver' => 'pdo_mysql'
                ),
                'mapping' => array(
                    'default' => array(
                        'namespace' => 'Example',
                        'paths' => array(
                            __DIR__
                        )
                    )
                )
            )
        ), $container);

        $calls = $container->getDefinition('doctrine.orm.configuration')->getMethodCalls();
        $this->assertCount(3, $calls);
        $lastMethodCall = array_pop($calls);
        $this->assertEquals(array('setProxyDir', array('%app.cache_dir%/doctrine')), $lastMethodCall);
    }

    /**
     * Test the getConfiguration method
     */
    public function testGetConfiguration()
    {
        $extension = new DoctrineOrmExtension();

        $container = new ContainerBuilder();
        $this->assertInstanceOf('Nice\Extension\DoctrineOrmConfiguration', $extension->getConfiguration(array(), $container));
    }
}
