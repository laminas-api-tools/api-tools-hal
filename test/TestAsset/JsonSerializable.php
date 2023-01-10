<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\TestAsset;

use Laminas\Stdlib\JsonSerializable as JsonSerializableInterface;
use ReturnTypeWillChange;

class JsonSerializable implements JsonSerializableInterface
{
    /**
     * @return array<string,string>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return ['foo' => 'bar'];
    }
}
