<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal;

use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Module;
use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\View;
use PHPUnit_Framework_TestCase as TestCase;
use stdClass;

class ModuleTest extends TestCase
{
    private $module;

    public function setUp()
    {
        $this->module = new Module;
    }

    public function testOnRenderWhenMvcEventResultIsNotHalJsonModel()
    {
        $mvcEvent = $this->getMockBuilder('Laminas\Mvc\MvcEvent')->getMock();
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
        $strategy = new HalJsonStrategy(new HalJsonRenderer(new ApiProblemRenderer()));

        $view = new View();

        $eventManager = $this->getMockBuilder('Laminas\EventManager\EventManager')->getMock();
        $eventManager
            ->expects($this->exactly(2))
            ->method('attach');

        $view->setEventManager($eventManager);

        $serviceManager = new ServiceManager();
        $serviceManager->setService('Laminas\ApiTools\Hal\JsonStrategy', $strategy);
        $serviceManager->setService('View', $view);

        $application = $this->getMockBuilder('Laminas\Mvc\ApplicationInterface')->getMock();
        $application
            ->expects($this->once())
            ->method('getServiceManager')
            ->will($this->returnValue($serviceManager));

        $mvcEvent = $this->getMockBuilder('Laminas\Mvc\MvcEvent')->getMock();
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
