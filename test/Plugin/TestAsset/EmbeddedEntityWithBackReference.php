<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

class EmbeddedEntityWithBackReference
{
    public $id;
    public $parent;

    public function __construct($id, Entity $parent)
    {
        $this->id   = $id;
        $this->parent = $parent;
    }
}
