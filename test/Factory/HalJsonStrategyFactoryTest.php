<?php

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\HalJsonStrategyFactory;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class HalJsonStrategyFactoryTest extends TestCase
{
    public function testInstantiatesHalJsonStrategy(): void
    {
        $halJsonRenderer = $this->createMock(HalJsonRenderer::class);

        $services = new ServiceManager();
        $services->setService('Laminas\ApiTools\Hal\JsonRenderer', $halJsonRenderer);

        $factory  = new HalJsonStrategyFactory();
        $strategy = $factory($services, HalJsonStrategy::class);

        self::assertInstanceOf(HalJsonStrategy::class, $strategy);
    }
}
