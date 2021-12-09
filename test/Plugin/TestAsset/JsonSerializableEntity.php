<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

use JsonSerializable;

class JsonSerializableEntity extends Entity implements JsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
        ];
    }
}
