<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\HalControllerPluginFactory;
use Laminas\ApiTools\Hal\Plugin\Hal as HalPlugin;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class HalControllerPluginFactoryTest extends TestCase
{
    public function testInstantiatesHalJsonRenderer()
    {
        $viewHelperManager = $this->getMockBuilder('Laminas\View\HelperPluginManager')
            ->disableOriginalConstructor()
            ->getMock();
        $viewHelperManager
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(new HalPlugin(new HydratorPluginManager(new ServiceManager()))));

        $services = new ServiceManager();
        $services->setService('ViewHelperManager', $viewHelperManager);

        $factory = new HalControllerPluginFactory();
        $plugin = $factory($services, 'Hal');

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Plugin\Hal', $plugin);
    }

    public function testInstantiatesHalJsonRendererWithV2()
    {
        $viewHelperManager = $this->getMockBuilder('Laminas\View\HelperPluginManager')
            ->disableOriginalConstructor()
            ->getMock();
        $viewHelperManager
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue(new HalPlugin(new HydratorPluginManager(new ServiceManager()))));

        $services = new ServiceManager();
        $services->setService('ViewHelperManager', $viewHelperManager);

        $pluginManager = $this->getMockBuilder('Laminas\ServiceManager\AbstractPluginManager')
            ->disableOriginalConstructor()
            ->getMock();
        $pluginManager->expects($this->once())
            ->method('getServiceLocator')
            ->willReturn($services);

        $factory = new HalControllerPluginFactory();
        $plugin = $factory->createService($pluginManager);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Plugin\Hal', $plugin);
    }
}
