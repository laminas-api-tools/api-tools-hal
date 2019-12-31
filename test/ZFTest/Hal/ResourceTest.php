<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Link\LinkCollection;
use Laminas\ApiTools\Hal\Resource;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ResourceTest extends TestCase
{
    public function invalidResources()
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
     * @dataProvider invalidResources
     */
    public function testConstructorRaisesExceptionForNonObjectNonArrayResource($resource)
    {
        $this->setExpectedException('Laminas\ApiTools\Hal\Exception\InvalidResourceException');
        $hal = new Resource($resource, 'id');
    }

    public function testPropertiesAreAccessibleAfterConstruction()
    {
        $resource = new stdClass;
        $hal      = new Resource($resource, 'id');
        $this->assertSame($resource, $hal->resource);
        $this->assertEquals('id', $hal->id);
    }

    public function testComposesLinkCollectionByDefault()
    {
        $resource = new stdClass;
        $hal      = new Resource($resource, 'id', 'route', array('foo' => 'bar'));
        $this->assertInstanceOf('Laminas\ApiTools\Hal\Link\LinkCollection', $hal->getLinks());
    }

    public function testLinkCollectionMayBeInjected()
    {
        $resource = new stdClass;
        $hal      = new Resource($resource, 'id', 'route', array('foo' => 'bar'));
        $links    = new LinkCollection();
        $hal->setLinks($links);
        $this->assertSame($links, $hal->getLinks());
    }
}
