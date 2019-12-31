<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\Hal\Module;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\View;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ModuleTest extends TestCase
{
    public function setUp()
    {
        $this->module = new Module;
    }

    public function testOnRenderWhenMvcEventResultIsNotHalJsonModel()
    {
        $mvcEvent = $this->getMock('Laminas\Mvc\MvcEvent');
        $mvcEvent
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue(new stdClass()));
        $mvcEvent
            ->expects($this->never())
            ->method('getTarget');

        $this->module->onRender($mvcEvent);
    }

    public function testOnRenderAttachesJsonStrategy()
    {
        $halJsonStrategy = $this->getMockBuilder('Laminas\ApiTools\Hal\View\HalJsonStrategy')
            ->disableOriginalConstructor()
            ->getMock();

        $view = new View();

        $eventManager = $this->getMock('Laminas\EventManager\EventManager');
        $eventManager
            ->expects($this->once())
            ->method('attach')
            ->with($halJsonStrategy, 200);

        $view->setEventManager($eventManager);

        $serviceManager = new ServiceManager();
        $serviceManager
            ->setService('Laminas\ApiTools\Hal\JsonStrategy', $halJsonStrategy)
            ->setService('View', $view);

        $application = $this->getMock('Laminas\Mvc\ApplicationInterface');
        $application
            ->expects($this->once())
            ->method('getServiceManager')
            ->will($this->returnValue($serviceManager));

        $mvcEvent = $this->getMock('Laminas\Mvc\MvcEvent');
        $mvcEvent
            ->expects($this->at(0))
            ->method('getResult')
            ->will($this->returnValue(new HalJsonModel()));
        $mvcEvent
            ->expects($this->at(1))
            ->method('getTarget')
            ->will($this->returnValue($application));

        $this->module->onRender($mvcEvent);
    }
}
