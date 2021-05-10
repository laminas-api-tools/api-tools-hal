<?php

namespace LaminasTest\ApiTools\Hal\TestAsset;

use Laminas\Stdlib\JsonSerializable as JsonSerializableInterface;

class JsonSerializable implements JsonSerializableInterface
{
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return ['foo' => 'bar'];
    }
}
