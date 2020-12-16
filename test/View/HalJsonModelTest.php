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
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @subpackage UnitTest
 */
class HalJsonModelTest extends TestCase
{
    /**
     * @var HalJsonModel
     */
    protected $model;

    public function setUp(): void
    {
        $this->model = new HalJsonModel;
    }

    public function testPayloadIsNullByDefault()
    {
        self::assertNull($this->model->getPayload());
    }

    public function testPayloadIsMutable()
    {
        $this->model->setPayload('foo');
        self::assertEquals('foo', $this->model->getPayload());
    }

    public function invalidPayloads()
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
            'array'      => [[]],
            'stdclass'   => [new stdClass],
        ];
    }

    public function invalidCollectionPayloads()
    {
        $payloads = $this->invalidPayloads();
        $payloads['exception'] = [new \Exception];
        $payloads['stdclass']  = [new stdClass];
        $payloads['hal-item']  = [new Entity([], 'id')];
        return $payloads;
    }

    /**
     * @dataProvider invalidCollectionPayloads
     *
     * @param mixed $payload
     */
    public function testIsCollectionReturnsFalseForInvalidValues($payload)
    {
        $this->model->setPayload($payload);
        self::assertFalse($this->model->isCollection());
    }

    public function testIsCollectionReturnsTrueForCollectionPayload()
    {
        $collection = new Collection([], 'item/route');
        $this->model->setPayload($collection);
        self::assertTrue($this->model->isCollection());
    }

    public function invalidEntityPayloads()
    {
        $payloads = $this->invalidPayloads();
        $payloads['exception']      = [new \Exception];
        $payloads['stdclass']       = [new stdClass];
        $payloads['hal-collection'] = [new Collection([], 'item/route')];
        return $payloads;
    }

    /**
     * @dataProvider invalidEntityPayloads
     *
     * @param mixed $payload
     */
    public function testIsEntityReturnsFalseForInvalidValues($payload)
    {
        $this->model->setPayload($payload);
        self::assertFalse($this->model->isEntity());
    }

    public function testIsEntityReturnsTrueForEntityPayload()
    {
        $item = new Entity([], 'id');
        $this->model->setPayload($item);
        self::assertTrue($this->model->isEntity());
    }

    public function testIsTerminalByDefault()
    {
        self::assertTrue($this->model->terminate());
    }

    /**
     * @depends testIsTerminalByDefault
     */
    public function testTerminalFlagIsNotMutable()
    {
        $this->model->setTerminal(false);
        self::assertTrue($this->model->terminate());
    }
}
