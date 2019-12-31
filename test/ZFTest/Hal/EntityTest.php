<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class EntityTest extends TestCase
{
    public function invalidEntities()
    {
        return array(
            'null'       => array(null),
            'true'       => array(true),
            'false'      => array(false),
            'zero-int'   => array(0),
            'int'        => array(1),
            'zero-float' => array(0.0),
            'float'      => array(1.1),
            'string'     => array('string'),
        );
    }

    /**
     * @dataProvider invalidEntities
     */
    public function testConstructorRaisesExceptionForNonObjectNonArrayEntity($entity)
    {
        $this->setExpectedException('Laminas\ApiTools\Hal\Exception\InvalidEntityException');
        $hal = new Entity($entity, 'id');
    }

    public function testPropertiesAreAccessibleAfterConstruction()
    {
        $entity   = new stdClass;
        $hal      = new Entity($entity, 'id');
        $this->assertSame($entity, $hal->entity);
        $this->assertEquals('id', $hal->id);
    }

    public function testComposesLinkCollectionByDefault()
    {
        $entity = new stdClass;
        $hal    = new Entity($entity, 'id', 'route', array('foo' => 'bar'));
        $this->assertInstanceOf('Laminas\ApiTools\Hal\Link\LinkCollection', $hal->getLinks());
    }

    public function testLinkCollectionMayBeInjected()
    {
        $entity   = new stdClass;
        $hal      = new Entity($entity, 'id', 'route', array('foo' => 'bar'));
        $links    = new LinkCollection();
        $hal->setLinks($links);
        $this->assertSame($links, $hal->getLinks());
    }

    public function testRetrievingEntityCanReturnByReference()
    {
        $entity   = array('foo' => 'bar');
        $hal      = new Entity($entity, 'id');
        $this->assertEquals($entity, $hal->entity);

        $entity =& $hal->entity;
        $entity['foo'] = 'baz';

        $secondRetrieval =& $hal->entity;
        $this->assertEquals('baz', $secondRetrieval['foo']);
    }
}
