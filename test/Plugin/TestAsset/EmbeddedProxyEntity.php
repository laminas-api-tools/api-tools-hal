<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

class EmbeddedProxyEntity extends EmbeddedEntity
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /**
     * @param string $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
