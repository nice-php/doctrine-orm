<?php

/*
 * Copyright (c) Tyler Sommer
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Nice\Tests\Extension;

use Nice\Extension\DoctrineOrmConfiguration;
use Symfony\Component\Config\Definition\Processor;

class DoctrineOrmConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testRequiresMappingElement()
    {
        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            'The child node "mapping" at path "doctrine_orm" must be configured.'
        );

        $processor = new Processor();
        $config = $processor->processConfiguration(
            new DoctrineOrmConfiguration(),
            array(
                'doctrine' => array(
                    'database' => array('driver' => 'pdo_mysql')
                )
            )
        );
    }

    public function testRequiresDatabaseElement()
    {
        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            'The child node "database" at path "doctrine_orm" must be configured.'
        );

        $processor = new Processor();
        $config = $processor->processConfiguration(
            new DoctrineOrmConfiguration(),
            array(
                'doctrine' => array(
                    'mapping' => array(
                        'default' => array(
                            'namespace' => 'Example',
                            'paths' => array(__DIR__)
                        )
                    )
                )
            )
        );
    }

    public function testDefaultOrmConfig()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(
            new DoctrineOrmConfiguration(),
            array(
                'doctrine' => array(
                    'mapping' => array(
                        'default' => array(
                            'namespace' => 'Example',
                            'paths' => array(__DIR__)
                        )
                    ),
                    'database' => array('driver' => 'pdo_mysql')
                )
            )
        );

        $this->assertEquals(
            array(
                'mapping' => array(
                    'default' => array(
                        'paths' => array(__DIR__),
                        'driver' => 'annotation',
                        'namespace' => 'Example'
                    )
                ),
                'database' => array('driver' => 'pdo_mysql')
            ),
            $config
        );
    }
}
