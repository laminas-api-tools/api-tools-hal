<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\HalJsonStrategyFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class HalJsonStrategyFactoryTest extends TestCase
{
    public function testInstantiatesHalJsonStrategy()
    {
        $services = new ServiceManager();

        $halJsonRenderer = $this->getMockBuilder('Laminas\ApiTools\Hal\View\HalJsonRenderer')
            ->disableOriginalConstructor()
            ->getMock();

        $services->setService('Laminas\ApiTools\Hal\JsonRenderer', $halJsonRenderer);

        $factory = new HalJsonStrategyFactory();
        $strategy = $factory->createService($services);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\View\HalJsonStrategy', $strategy);
    }
}
