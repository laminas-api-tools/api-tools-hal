<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Plugin\TestAsset;

class Resource
{
    public $id;
    public $name;

    public $first_child;
    public $second_child;

    public function __construct($id, $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}
