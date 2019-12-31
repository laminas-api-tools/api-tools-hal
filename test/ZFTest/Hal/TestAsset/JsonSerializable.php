<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\TestAsset;

use JsonSerializable as JsonSerializableInterface;

/**
 * @subpackage UnitTest
 */
class JsonSerializable implements JsonSerializableInterface
{
    public function jsonSerialize()
    {
        return array('foo' => 'bar');
    }
}
