<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\TestAsset;

/**
 * See LaminasTest\ApiTools\Hal\ResourceFactoryTest::testRouteParamsAllowsCallable
 */
class EntityDefiningCallback
{
    private $expected;
    private $phpunit;

    public function __construct($phpunit, $expected)
    {
        $this->phpunit  = $phpunit;
        $this->expected = $expected;
    }

    public function callback($value)
    {
        $this->phpunit->assertSame($this->expected, $value);
        return 'callback-param';
    }
}
