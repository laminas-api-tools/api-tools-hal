<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

class EmbeddedEntityWithBackReference
{
    /** @var string */
    public $id;

    /** @var Entity */
    public $parent;

    /**
     * @param string $id
     */
    public function __construct($id, Entity $parent)
    {
        $this->id     = $id;
        $this->parent = $parent;
    }
}
