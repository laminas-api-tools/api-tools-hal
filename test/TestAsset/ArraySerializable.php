<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\TestAsset;

class ArraySerializable
{
    /**
     * @return string
     */
    public function getHijinx()
    {
        return 'should not get this';
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return ['foo' => 'bar'];
    }
}
