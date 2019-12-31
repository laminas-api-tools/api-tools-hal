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
            ->method('get')
            ->with('Laminas\ApiTools\Hal\HalConfig')
            ->will($this->returnValue([]));

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

        $config = [
            'metadata_map' => [
                'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity' => [
                    'hydrator'   => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                    'route_name' => 'hostname/resource',
                    'route_identifier_name' => 'id',
                    'entity_identifier_name' => 'id',
                ],
                'LaminasTest\ApiTools\Hal\Plugin\TestAsset\EmbeddedEntity' => [
                    'hydrator' => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                    'route'    => 'hostname/embedded',
                    'route_identifier_name' => 'id',
                    'entity_identifier_name' => 'id',
                ],
                'LaminasTest\ApiTools\Hal\Plugin\TestAsset\EmbeddedEntityWithCustomIdentifier' => [
                    'hydrator'        => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                    'route'           => 'hostname/embedded_custom',
                    'route_identifier_name' => 'custom_id',
                    'entity_identifier_name' => 'custom_id',
                ],
            ],
        ];

        $services
            ->expects($this->at(0))
            ->method('get')
            ->with('Laminas\ApiTools\Hal\HalConfig')
            ->will($this->returnValue($config));

        $services
            ->expects($this->at(1))
            ->method('has')
            ->with('HydratorManager')
            ->will($this->returnValue(false));

        $factory = new MetadataMapFactory();
        $metadataMap = $factory->createService($services);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Metadata\MetadataMap', $metadataMap);

        foreach ($config['metadata_map'] as $key => $value) {
            $this->assertTrue($metadataMap->has($key));
            $this->assertInstanceOf('Laminas\ApiTools\Hal\Metadata\Metadata', $metadataMap->get($key));
        }
    }
}
