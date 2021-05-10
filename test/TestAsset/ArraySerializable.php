<?php

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
