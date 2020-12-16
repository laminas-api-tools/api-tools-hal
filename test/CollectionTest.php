<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Exception\InvalidCollectionException;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit\Framework\TestCase;
use stdClass;

class CollectionTest extends TestCase
{
    public function invalidCollections(): array
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
     *
     * @param mixed $collection
     */
    public function testConstructorRaisesExceptionForNonTraversableCollection($collection): void
    {
        $this->expectException(InvalidCollectionException::class);

        new Collection($collection, 'collection/route', 'item/route');
    }

    public function testPropertiesAreAccessibleFollowingConstruction(): void
    {
        $hal = new Collection([], 'item/route', ['version' => 1], ['query' => 'format=json']);

        self::assertEquals([], $hal->getCollection());
        self::assertEquals('item/route', $hal->getEntityRoute());
        self::assertEquals(['version' => 1], $hal->getEntityRouteParams());
        self::assertEquals(['query' => 'format=json'], $hal->getEntityRouteOptions());
    }

    public function testDefaultPageIsOne(): void
    {
        $hal = new Collection([], 'item/route');

        self::assertEquals(1, $hal->getPage());
    }

    public function testPageIsMutable(): void
    {
        $hal = new Collection([], 'item/route');
        $hal->setPage(5);

        self::assertEquals(5, $hal->getPage());
    }

    public function testDefaultPageSizeIsThirty(): void
    {
        $hal = new Collection([], 'item/route');

        self::assertEquals(30, $hal->getPageSize());
    }

    public function testPageSizeIsMutable(): void
    {
        $hal = new Collection([], 'item/route');
        $hal->setPageSize(3);

        self::assertEquals(3, $hal->getPageSize());
    }

    public function testPageSizeAllowsNegativeOneAsValue(): void
    {
        $hal = new Collection([], 'item/route');
        $hal->setPageSize(-1);

        self::assertEquals(-1, $hal->getPageSize());
    }

    public function testDefaultCollectionNameIsItems(): void
    {
        $hal = new Collection([], 'item/route');

        self::assertEquals('items', $hal->getCollectionName());
    }

    public function testCollectionNameIsMutable(): void
    {
        $hal = new Collection([], 'item/route');
        $hal->setCollectionName('records');

        self::assertEquals('records', $hal->getCollectionName());
    }

    public function testDefaultAttributesAreEmpty(): void
    {
        $hal = new Collection([], 'item/route');

        self::assertEquals([], $hal->getAttributes());
    }

    public function testAttributesAreMutable(): void
    {
        $hal = new Collection([], 'item/route');
        $attributes = [
            'count' => 1376,
            'order' => 'desc',
        ];
        $hal->setAttributes($attributes);

        self::assertEquals($attributes, $hal->getAttributes());
    }

    public function testComposesLinkCollectionByDefault(): void
    {
        $hal = new Collection([], 'item/route');

        self::assertInstanceOf(LinkCollection::class, $hal->getLinks());
    }

    public function testLinkCollectionMayBeInjected(): void
    {
        $hal   = new Collection([], 'item/route');
        $links = new LinkCollection();
        $hal->setLinks($links);

        self::assertSame($links, $hal->getLinks());
    }

    public function testAllowsSettingAdditionalEntityLinks(): void
    {
        $links = new LinkCollection();
        $links->add(new Link('describedby'));
        $links->add(new Link('orders'));
        $hal   = new Collection([], 'item/route');
        $hal->setEntityLinks($links);

        self::assertSame($links, $hal->getEntityLinks());
    }
}
