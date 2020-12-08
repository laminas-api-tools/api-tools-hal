<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Factory\MetadataMapFactory;
use Laminas\ApiTools\Hal\Metadata\Metadata;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\Hydrator\ObjectProperty;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class MetadataMapFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInstantiatesMetadataMapWithEmptyConfig(): void
    {
        $services = $this->prophesize(ContainerInterface::class);
        $services->get('Laminas\ApiTools\Hal\HalConfig')->willReturn([]);
        $services->has('HydratorManager')->willReturn(false);

        $factory     = new MetadataMapFactory();
        $metadataMap = $factory($services->reveal(), MetadataMap::class);

        self::assertInstanceOf(MetadataMap::class, $metadataMap);
    }

    public function testInstantiatesMetadataMapWithMetadataMapConfig(): void
    {
        $config = [
            'metadata_map' => [
                TestAsset\Entity::class => [
                    'hydrator'   => ObjectProperty::class,
                    'route_name' => 'hostname/resource',
                    'route_identifier_name' => 'id',
                    'entity_identifier_name' => 'id',
                ],
                TestAsset\EmbeddedEntity::class => [
                    'hydrator' => ObjectProperty::class,
                    'route'    => 'hostname/embedded',
                    'route_identifier_name' => 'id',
                    'entity_identifier_name' => 'id',
                ],
                TestAsset\EmbeddedEntityWithCustomIdentifier::class => [
                    'hydrator'        => ObjectProperty::class,
                    'route'           => 'hostname/embedded_custom',
                    'route_identifier_name' => 'custom_id',
                    'entity_identifier_name' => 'custom_id',
                ],
            ],
        ];

        $services = $this->prophesize(ContainerInterface::class);
        $services->get('Laminas\ApiTools\Hal\HalConfig')->willReturn($config);
        $services->has('HydratorManager')->willReturn(false);

        $factory = new MetadataMapFactory();
        $metadataMap = $factory($services->reveal(), MetadataMap::class);

        foreach ($config['metadata_map'] as $key => $value) {
            self::assertTrue($metadataMap->has($key));
            self::assertInstanceOf(Metadata::class, $metadataMap->get($key));
        }
    }
}
