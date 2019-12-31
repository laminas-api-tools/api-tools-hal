<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\MetadataMapFactory;
use PHPUnit_Framework_TestCase as TestCase;

class MetadataMapFactoryTest extends TestCase
{
    public function testInstantiatesMetadataMapWithEmptyConfig()
    {
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');

        $services
            ->expects($this->at(0))
            ->method('has')
            ->with('config')
            ->will($this->returnValue(false));

        $services
            ->expects($this->at(1))
            ->method('has')
            ->with('HydratorManager')
            ->will($this->returnValue(false));

        $factory = new MetadataMapFactory();
        $renderer = $factory->createService($services);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Metadata\MetadataMap', $renderer);
    }

    public function testInstantiatesMetadataMapWithMetadataMapConfig()
    {
        $services = $this->getMock('Laminas\ServiceManager\ServiceLocatorInterface');

        $services
            ->expects($this->at(0))
            ->method('has')
            ->with('config')
            ->will($this->returnValue(true));

        $config = array(
            'api-tools-hal' => array(
                'metadata_map' => array(
                    'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity' => array(
                        'hydrator'   => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                        'route_name' => 'hostname/resource',
                        'route_identifier_name' => 'id',
                        'entity_identifier_name' => 'id',
                    ),
                    'LaminasTest\ApiTools\Hal\Plugin\TestAsset\EmbeddedEntity' => array(
                        'hydrator' => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                        'route'    => 'hostname/embedded',
                        'route_identifier_name' => 'id',
                        'entity_identifier_name' => 'id',
                    ),
                    'LaminasTest\ApiTools\Hal\Plugin\TestAsset\EmbeddedEntityWithCustomIdentifier' => array(
                        'hydrator'        => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                        'route'           => 'hostname/embedded_custom',
                        'route_identifier_name' => 'custom_id',
                        'entity_identifier_name' => 'custom_id',
                    ),
                ),
            ),
        );

        $services
            ->expects($this->at(1))
            ->method('get')
            ->with('config')
            ->will($this->returnValue($config));

        $services
            ->expects($this->at(2))
            ->method('has')
            ->with('HydratorManager')
            ->will($this->returnValue(false));

        $factory = new MetadataMapFactory();
        $metadataMap = $factory->createService($services);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Metadata\MetadataMap', $metadataMap);

        foreach ($config['api-tools-hal']['metadata_map'] as $key => $value) {
            $this->assertTrue($metadataMap->has($key));
            $this->assertInstanceOf('Laminas\ApiTools\Hal\Metadata\Metadata', $metadataMap->get($key));
        }
    }
}
