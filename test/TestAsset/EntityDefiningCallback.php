<?php

namespace LaminasTest\ApiTools\Hal\TestAsset;

use PHPUnit\Framework\TestCase;

/**
 * See LaminasTest\ApiTools\Hal\ResourceFactoryTest::testRouteParamsAllowsCallable
 */
class EntityDefiningCallback
{
    /** @var mixed */
    private $expected;

    /** @var TestCase */
    private $phpunit;

    /**
     * @param TestCase $phpunit
     * @param mixed $expected
     */
    public function __construct($phpunit, $expected)
    {
        $this->phpunit  = $phpunit;
        $this->expected = $expected;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function callback($value)
    {
        $this->phpunit->assertSame($this->expected, $value);
        return 'callback-param';
    }
}
