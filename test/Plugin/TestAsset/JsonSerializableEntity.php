<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

use JsonSerializable;
use ReturnTypeWillChange;

class JsonSerializableEntity extends Entity implements JsonSerializable
{
    /**
     * @return array<string,string>
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
        ];
    }
}
