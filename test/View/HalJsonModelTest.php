<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\View;

use Exception;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use PHPUnit\Framework\TestCase;
use stdClass;

class HalJsonModelTest extends TestCase
{
    /** @var HalJsonModel */
    protected $model;

    public function setUp(): void
    {
        $this->model = new HalJsonModel();
    }

    public function testPayloadIsNullByDefault(): void
    {
        self::assertNull($this->model->getPayload());
    }

    public function testPayloadIsMutable(): void
    {
        $this->model->setPayload('foo');
        self::assertEquals('foo', $this->model->getPayload());
    }

    /**
     * @return array
     */
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
            'stdclass'   => [new stdClass()],
        ];
    }

    /**
     * @return array
     */
    public function invalidCollectionPayloads()
    {
        $payloads              = $this->invalidPayloads();
        $payloads['exception'] = [new Exception()];
        $payloads['stdclass']  = [new stdClass()];
        $payloads['hal-item']  = [new Entity([], 'id')];
        return $payloads;
    }

    /**
     * @dataProvider invalidCollectionPayloads
     * @param mixed $payload
     */
    public function testIsCollectionReturnsFalseForInvalidValues($payload): void
    {
        $this->model->setPayload($payload);
        self::assertFalse($this->model->isCollection());
    }

    public function testIsCollectionReturnsTrueForCollectionPayload(): void
    {
        $collection = new Collection([], 'item/route');
        $this->model->setPayload($collection);
        self::assertTrue($this->model->isCollection());
    }

    /**
     * @return array
     */
    public function invalidEntityPayloads()
    {
        $payloads                   = $this->invalidPayloads();
        $payloads['exception']      = [new Exception()];
        $payloads['stdclass']       = [new stdClass()];
        $payloads['hal-collection'] = [new Collection([], 'item/route')];
        return $payloads;
    }

    /**
     * @dataProvider invalidEntityPayloads
     * @param mixed $payload
     */
    public function testIsEntityReturnsFalseForInvalidValues($payload): void
    {
        $this->model->setPayload($payload);
        self::assertFalse($this->model->isEntity());
    }

    public function testIsEntityReturnsTrueForEntityPayload(): void
    {
        $item = new Entity([], 'id');
        $this->model->setPayload($item);
        self::assertTrue($this->model->isEntity());
    }

    public function testIsTerminalByDefault(): void
    {
        self::assertTrue($this->model->terminate());
    }

    /**
     * @depends testIsTerminalByDefault
     */
    public function testTerminalFlagIsNotMutable(): void
    {
        $this->model->setTerminal(false);
        self::assertTrue($this->model->terminate());
    }
}
