<?php

declare(strict_types=1);

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\HalControllerPluginFactory;
use Laminas\ApiTools\Hal\Plugin\Hal as HalPlugin;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\HelperPluginManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class HalControllerPluginFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testInstantiatesHalJsonRenderer(): void
    {
        $viewHelperManager = $this->prophesize(HelperPluginManager::class);
        $viewHelperManager->get('Hal')
            ->willReturn(new HalPlugin(new HydratorPluginManager(new ServiceManager())))
            ->shouldBeCalledTimes(1);

        $services = new ServiceManager();
        $services->setService('ViewHelperManager', $viewHelperManager->reveal());

        $factory = new HalControllerPluginFactory();
        $plugin  = $factory($services, 'Hal');

        self::assertInstanceOf(HalPlugin::class, $plugin);
    }

    public function testInstantiatesHalJsonRendererWithV2(): void
    {
        $viewHelperManager = $this->prophesize(HelperPluginManager::class);
        $viewHelperManager->get('Hal')
            ->willReturn(new HalPlugin(new HydratorPluginManager(new ServiceManager())))
            ->shouldBeCalledTimes(1);

        $services = new ServiceManager();
        $services->setService('ViewHelperManager', $viewHelperManager->reveal());

        $pluginManager = $this->prophesize(AbstractPluginManager::class);
        $pluginManager->getServiceLocator()
            ->willReturn($services)
            ->shouldBeCalledTimes(1);

        $factory = new HalControllerPluginFactory();
        $plugin  = $factory->createService($pluginManager->reveal());

        self::assertInstanceOf(HalPlugin::class, $plugin);
    }
}
