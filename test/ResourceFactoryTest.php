<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\ApiTools\Hal\Extractor\EntityExtractor;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\ApiTools\Hal\ResourceFactory;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Hydrator\ObjectProperty;
use Laminas\ServiceManager\ServiceManager;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset as HalPluginTestAsset;
use PHPUnit\Framework\TestCase;

/**
 * @subpackage UnitTest
 */
class ResourceFactoryTest extends TestCase
{
    /**
     * @group 79
     */
    public function testInjectsLinksFromMetadataWhenCreatingEntity(): void
    {
        $object = new HalPluginTestAsset\Entity('foo', 'Foo');

        $metadata = new MetadataMap([
            HalPluginTestAsset\Entity::class => [
                'hydrator'   => 'Laminas\Hydrator\ObjectProperty',
                'route_name' => 'hostname/resource',
                'links'      => [
                    [
                        'rel' => 'describedby',
                        'href' => 'http://example.com/api/help/resource',
                    ],
                    [
                        'rel' => 'children',
                        'route' => [
                            'name' => 'resource/children',
                        ],
                    ],
                ],
            ],
        ]);
        $metadata->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $resourceFactory = $this->getResourceFactory($metadata);

        $entity = $resourceFactory->createEntityFromMetadata(
            $object,
            $metadata->get(HalPluginTestAsset\Entity::class)
        );

        $this->assertInstanceOf(Entity::class, $entity);
        $links = $entity->getLinks();
        $this->assertTrue($links->has('describedby'));
        $this->assertTrue($links->has('children'));

        $describedby = $links->get('describedby');
        $this->assertTrue($describedby->hasUrl());
        $this->assertEquals('http://example.com/api/help/resource', $describedby->getUrl());

        $children = $links->get('children');
        $this->assertTrue($children->hasRoute());
        $this->assertEquals('resource/children', $children->getRoute());
    }

    /**
     * Test that the hal metadata route params config allows callables.
     *
     * All callables should be passed the object being used for entity creation.
     * If closure binding is supported, any closures should be bound to that
     * object.
     *
     * The return value should be used as the route param for the link (in
     * place of the callable).
     */
    public function testRouteParamsAllowsCallable(): void
    {
        $object = new HalPluginTestAsset\Entity('foo', 'Foo');

        $entityDefiningCallback = new TestAsset\EntityDefiningCallback($this, $object);

        $test = $this;

        $metadata = new MetadataMap([
            HalPluginTestAsset\Entity::class => [
                'hydrator'     => ObjectProperty::class,
                'route_name'   => 'hostname/resource',
                'route_params' => [
                    'test-1' => [$entityDefiningCallback, 'callback'],
                    'test-2' => function ($expected) use ($object, $test) {
                        $test->assertSame($expected, $object);
                        $test->assertSame($object, $this);

                        return 'closure-param';
                    },
                ],
            ],
        ]);

        $resourceFactory = $this->getResourceFactory($metadata);

        $entity = $resourceFactory->createEntityFromMetadata(
            $object,
            $metadata->get(HalPluginTestAsset\Entity::class)
        );

        $this->assertInstanceOf(Entity::class, $entity);

        $links = $entity->getLinks();
        $this->assertTrue($links->has('self'));

        $self = $links->get('self');
        $params = $self->getRouteParams();

        $this->assertArrayHasKey('test-1', $params);
        $this->assertEquals('callback-param', $params['test-1']);

        $this->assertArrayHasKey('test-2', $params);
        $this->assertEquals('closure-param', $params['test-2']);
    }

    /**
     * @group 79
     */
    public function testInjectsLinksFromMetadataWhenCreatingCollection(): void
    {
        $set = new HalPluginTestAsset\Collection([
            (object) ['id' => 'foo', 'name' => 'foo'],
            (object) ['id' => 'bar', 'name' => 'bar'],
            (object) ['id' => 'baz', 'name' => 'baz'],
        ]);

        $metadata = new MetadataMap([
            HalPluginTestAsset\Collection::class => [
                'is_collection'       => true,
                'route_name'          => 'hostname/contacts',
                'entity_route_name'   => 'hostname/embedded',
                'links'               => [
                    [
                        'rel' => 'describedby',
                        'href' => 'http://example.com/api/help/collection',
                    ],
                ],
            ],
        ]);
        $metadata->setHydratorManager(new HydratorPluginManager(new ServiceManager()));

        $resourceFactory = $this->getResourceFactory($metadata);

        $collection = $resourceFactory->createCollectionFromMetadata(
            $set,
            $metadata->get(HalPluginTestAsset\Collection::class)
        );

        $this->assertInstanceOf(Collection::class, $collection);
        $links = $collection->getLinks();
        $this->assertTrue($links->has('describedby'));
        $link = $links->get('describedby');
        $this->assertTrue($link->hasUrl());
        $this->assertEquals('http://example.com/api/help/collection', $link->getUrl());
    }

    private function getResourceFactory(MetadataMap $metadata): ResourceFactory
    {
        $hydratorPluginManager = new HydratorPluginManager(new ServiceManager());
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadata);
        $entityExtractor       = new EntityExtractor($entityHydratorManager);

        return new ResourceFactory($entityHydratorManager, $entityExtractor);
    }
}
