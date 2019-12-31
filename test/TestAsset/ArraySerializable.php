<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\TestAsset;

/**
 * @subpackage UnitTest
 */
class ArraySerializable
{
    public function getHijinx()
    {
        return 'should not get this';
    }

    public function getArrayCopy()
    {
        return array('foo' => 'bar');
    }
}
