<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\EntityHydratorManager;
use Laminas\ApiTools\Hal\Extractor\EntityExtractor;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\ApiTools\Hal\ResourceFactory;
use Laminas\Stdlib\Hydrator\HydratorPluginManager;
use LaminasTest\ApiTools\Hal\Plugin\TestAsset;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @subpackage UnitTest
 */
class ResourceFactoryTest extends TestCase
{
    /**
     * @group 79
     */
    public function testInjectsLinksFromMetadataWhenCreatingEntity()
    {
        $object = new TestAsset\Entity('foo', 'Foo');

        $metadata = new MetadataMap([
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity' => [
                'hydrator'   => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                'route_name' => 'hostname/resource',
                'links'      => [
                    [
                        'rel' => 'describedby',
                        'url' => 'http://example.com/api/help/resource',
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

        $resourceFactory = $this->getResourceFactory($metadata);

        $entity = $resourceFactory->createEntityFromMetadata(
            $object,
            $metadata->get('LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity')
        );

        $this->assertInstanceof('Laminas\ApiTools\Hal\Entity', $entity);
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
    public function testRouteParamsAllowsCallable()
    {
        $object = new TestAsset\Entity('foo', 'Foo');

        $callback = $this->getMock('stdClass', ['callback']);
        $callback->expects($this->atLeastOnce())
                 ->method('callback')
                 ->with($this->equalTo($object))
                 ->will($this->returnValue('callback-param'));

        $test = $this;

        $metadata = new MetadataMap([
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity' => [
                'hydrator'     => 'Laminas\Stdlib\Hydrator\ObjectProperty',
                'route_name'   => 'hostname/resource',
                'route_params' => [
                    'test-1' => [$callback, 'callback'],
                    'test-2' => function ($expected) use ($object, $test) {
                        $test->assertSame($expected, $object);
                        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
                            $test->assertSame($object, $this);
                        }

                        return 'closure-param';
                    },
                ],
            ],
        ]);

        $resourceFactory = $this->getResourceFactory($metadata);

        $entity = $resourceFactory->createEntityFromMetadata(
            $object,
            $metadata->get('LaminasTest\ApiTools\Hal\Plugin\TestAsset\Entity')
        );

        $this->assertInstanceof('Laminas\ApiTools\Hal\Entity', $entity);

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
    public function testInjectsLinksFromMetadataWhenCreatingCollection()
    {
        $set = new TestAsset\Collection([
            (object) ['id' => 'foo', 'name' => 'foo'],
            (object) ['id' => 'bar', 'name' => 'bar'],
            (object) ['id' => 'baz', 'name' => 'baz'],
        ]);

        $metadata = new MetadataMap([
            'LaminasTest\ApiTools\Hal\Plugin\TestAsset\Collection' => [
                'is_collection'       => true,
                'route_name'          => 'hostname/contacts',
                'entity_route_name'   => 'hostname/embedded',
                'links'               => [
                    [
                        'rel' => 'describedby',
                        'url' => 'http://example.com/api/help/collection',
                    ],
                ],
            ],
        ]);

        $resourceFactory = $this->getResourceFactory($metadata);

        $collection = $resourceFactory->createCollectionFromMetadata(
            $set,
            $metadata->get('LaminasTest\ApiTools\Hal\Plugin\TestAsset\Collection')
        );

        $this->assertInstanceof('Laminas\ApiTools\Hal\Collection', $collection);
        $links = $collection->getLinks();
        $this->assertTrue($links->has('describedby'));
        $link = $links->get('describedby');
        $this->assertTrue($link->hasUrl());
        $this->assertEquals('http://example.com/api/help/collection', $link->getUrl());
    }

    private function getResourceFactory(MetadataMap $metadata)
    {
        $hydratorPluginManager = new HydratorPluginManager();
        $entityHydratorManager = new EntityHydratorManager($hydratorPluginManager, $metadata);
        $entityExtractor       = new EntityExtractor($entityHydratorManager);

        return new ResourceFactory($entityHydratorManager, $entityExtractor);
    }
}
