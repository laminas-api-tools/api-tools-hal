<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\View;

use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

/**
 * @subpackage UnitTest
 */
class HalJsonModelTest extends TestCase
{
    public function setUp()
    {
        $this->model = new HalJsonModel;
    }

    public function testPayloadIsNullByDefault()
    {
        $this->assertNull($this->model->getPayload());
    }

    public function testPayloadIsMutable()
    {
        $this->model->setPayload('foo');
        $this->assertEquals('foo', $this->model->getPayload());
    }

    public function invalidPayloads()
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
            'array'      => array(array()),
            'stdclass'   => array(new stdClass),
        );
    }

    public function invalidCollectionPayloads()
    {
        $payloads = $this->invalidPayloads();
        $payloads['exception'] = array(new \Exception);
        $payloads['stdclass']  = array(new stdClass);
        $payloads['hal-item']  = array(new Entity(array(), 'id', 'route'));
        return $payloads;
    }

    /**
     * @dataProvider invalidCollectionPayloads
     */
    public function testIsCollectionReturnsFalseForInvalidValues($payload)
    {
        $this->model->setPayload($payload);
        $this->assertFalse($this->model->isCollection());
    }

    public function testIsCollectionReturnsTrueForCollectionPayload()
    {
        $collection = new Collection(array(), 'item/route');
        $this->model->setPayload($collection);
        $this->assertTrue($this->model->isCollection());
    }

    public function invalidEntityPayloads()
    {
        $payloads = $this->invalidPayloads();
        $payloads['exception']      = array(new \Exception);
        $payloads['stdclass']       = array(new stdClass);
        $payloads['hal-collection'] = array(new Collection(array(), 'item/route'));
        return $payloads;
    }

    /**
     * @dataProvider invalidEntityPayloads
     */
    public function testIsEntityReturnsFalseForInvalidValues($payload)
    {
        $this->model->setPayload($payload);
        $this->assertFalse($this->model->isEntity());
    }

    public function testIsEntityReturnsTrueForEntityPayload()
    {
        $item = new Entity(array(), 'id', 'route');
        $this->model->setPayload($item);
        $this->assertTrue($this->model->isEntity());
    }

    public function testIsTerminalByDefault()
    {
        $this->assertTrue($this->model->terminate());
    }

    /**
     * @depends testIsTerminalByDefault
     */
    public function testTerminalFlagIsNotMutable()
    {
        $this->model->setTerminal(false);
        $this->assertTrue($this->model->terminate());
    }
}
