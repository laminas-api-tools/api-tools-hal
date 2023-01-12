<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

use JsonSerializable;

class JsonSerializableEntity extends Entity implements JsonSerializable
{
    /**
     * @return array<string,string>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
