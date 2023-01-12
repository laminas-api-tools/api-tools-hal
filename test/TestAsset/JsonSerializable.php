<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\TestAsset;

use Laminas\Stdlib\JsonSerializable as JsonSerializableInterface;

class JsonSerializable implements JsonSerializableInterface
{
    /**
     * @return array<string,string>
     */
    public function jsonSerialize(): array
    {
        return ['foo' => 'bar'];
    }
}
