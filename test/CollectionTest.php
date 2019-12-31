<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class CollectionTest extends TestCase
{
    public function invalidCollections()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero-int'   => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['string'],
            'stdclass'   => [new stdClass],
        ];
    }

    /**
     * @dataProvider invalidCollections
     */
    public function testConstructorRaisesExceptionForNonTraversableCollection($collection)
    {
        $this->setExpectedException('Laminas\ApiTools\Hal\Exception\InvalidCollectionException');
        $hal = new Collection($collection, 'collection/route', 'item/route');
    }

    public function testPropertiesAreAccessibleFollowingConstruction()
    {
        $hal = new Collection([], 'item/route', ['version' => 1], ['query' => 'format=json']);
        $this->assertEquals([], $hal->getCollection());
        $this->assertEquals('item/route', $hal->getEntityRoute());
        $this->assertEquals(['version' => 1], $hal->getEntityRouteParams());
        $this->assertEquals(['query' => 'format=json'], $hal->getEntityRouteOptions());
    }

    public function testDefaultPageIsOne()
    {
        $hal = new Collection([], 'item/route');
        $this->assertEquals(1, $hal->getPage());
    }

    public function testPageIsMutable()
    {
        $hal = new Collection([], 'item/route');
        $hal->setPage(5);
        $this->assertEquals(5, $hal->getPage());
    }

    public function testDefaultPageSizeIsThirty()
    {
        $hal = new Collection([], 'item/route');
        $this->assertEquals(30, $hal->getPageSize());
    }

    public function testPageSizeIsMutable()
    {
        $hal = new Collection([], 'item/route');
        $hal->setPageSize(3);
        $this->assertEquals(3, $hal->getPageSize());
    }

    public function testPageSizeAllowsNegativeOneAsValue()
    {
        $hal = new Collection([], 'item/route');
        $hal->setPageSize(-1);
        $this->assertEquals(-1, $hal->getPageSize());
    }

    public function testDefaultCollectionNameIsItems()
    {
        $hal = new Collection([], 'item/route');
        $this->assertEquals('items', $hal->getCollectionName());
    }

    public function testCollectionNameIsMutable()
    {
        $hal = new Collection([], 'item/route');
        $hal->setCollectionName('records');
        $this->assertEquals('records', $hal->getCollectionName());
    }

    public function testDefaultAttributesAreEmpty()
    {
        $hal = new Collection([], 'item/route');
        $this->assertEquals([], $hal->getAttributes());
    }

    public function testAttributesAreMutable()
    {
        $hal = new Collection([], 'item/route');
        $attributes = [
            'count' => 1376,
            'order' => 'desc',
        ];
        $hal->setAttributes($attributes);
        $this->assertEquals($attributes, $hal->getAttributes());
    }

    public function testComposesLinkCollectionByDefault()
    {
        $hal = new Collection([], 'item/route');
        $this->assertInstanceOf('Laminas\ApiTools\Hal\Link\LinkCollection', $hal->getLinks());
    }

    public function testLinkCollectionMayBeInjected()
    {
        $hal   = new Collection([], 'item/route');
        $links = new LinkCollection();
        $hal->setLinks($links);
        $this->assertSame($links, $hal->getLinks());
    }

    public function testAllowsSettingAdditionalEntityLinks()
    {
        $links = new LinkCollection();
        $links->add(new Link('describedby'));
        $links->add(new Link('orders'));
        $hal   = new Collection([], 'item/route');
        $hal->setEntityLinks($links);
        $this->assertSame($links, $hal->getEntityLinks());
    }
}
