<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Exception\InvalidEntityException;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit\Framework\TestCase;
use stdClass;

class EntityTest extends TestCase
{
    public function invalidEntities(): array
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
        ];
    }

    /**
     * @dataProvider invalidEntities
     *
     * @param mixed $entity
     */
    public function testConstructorRaisesExceptionForNonObjectNonArrayEntity($entity): void
    {
        $this->expectException(InvalidEntityException::class);

        new Entity($entity, 'id');
    }

    public function testPropertiesAreAccessibleAfterConstruction(): void
    {
        $entity = new stdClass;
        $hal    = new Entity($entity, 'id');

        $this->assertSame($entity, $hal->getEntity());
        $this->assertEquals('id', $hal->getId());
    }

    public function testComposesLinkCollectionByDefault(): void
    {
        $entity = new stdClass;
        $hal    = new Entity($entity, 'id', 'route', ['foo' => 'bar']);

        $this->assertInstanceOf(LinkCollection::class, $hal->getLinks());
    }

    public function testLinkCollectionMayBeInjected(): void
    {
        $entity = new stdClass;
        $hal    = new Entity($entity, 'id', 'route', ['foo' => 'bar']);
        $links  = new LinkCollection();
        $hal->setLinks($links);

        $this->assertSame($links, $hal->getLinks());
    }

    public function testRetrievingEntityCanReturnByReference(): void
    {
        $entity = ['foo' => 'bar'];
        $hal    = new Entity($entity, 'id');
        $this->assertEquals($entity, $hal->getEntity());

        $entity =& $hal->getEntity();
        $entity['foo'] = 'baz';

        $secondRetrieval =& $hal->getEntity();
        $this->assertEquals('baz', $secondRetrieval['foo']);
    }

    /**
     * @group 39
     */
    public function testConstructorAllowsNullIdentifier(): void
    {
        $hal = new Entity(['foo' => 'bar'], null);
        $this->assertNull($hal->getId());
    }

    public function magicProperties(): array
    {
        return [
            'entity' => ['entity'],
            'id'     => ['id'],
        ];
    }

    /**
     * @group 99
     * @dataProvider magicProperties
     */
    public function testPropertyRetrievalEmitsDeprecationNotice($property): void
    {
        $entity    = ['foo' => 'bar'];
        $hal       = new Entity($entity, 'id');
        $triggered = false;

        \set_error_handler(function ($errno, $errstr) use (&$triggered) {
            $triggered = true;
            $this->assertStringContainsString('Direct property access', $errstr);
        }, E_USER_DEPRECATED);
        $hal->$property;
        \restore_error_handler();

        $this->assertTrue($triggered, 'Deprecation notice was not triggered!');
    }
}
