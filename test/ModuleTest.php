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
use Laminas\EventManager\EventManager;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\View;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use stdClass;

class ModuleTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var Module
     */
    private $module;

    public function setUp(): void
    {
        $this->module = new Module();
    }

    public function testOnRenderWhenMvcEventResultIsNotHalJsonModel(): void
    {
        $mvcEvent = $this->prophesize(MvcEvent::class);
        $mvcEvent->getResult()->willReturn(new stdClass())->shouldBeCalledTimes(1);
        $mvcEvent->getTarget()->shouldNotBeCalled();

        $this->module->onRender($mvcEvent->reveal());
    }

    public function testOnRenderAttachesJsonStrategy(): void
    {
        $strategy = new HalJsonStrategy(new HalJsonRenderer(new ApiProblemRenderer()));

        $view = new View();

        $eventManager = $this->createMock(EventManager::class);
        $eventManager
            ->expects($this->exactly(2))
            ->method('attach');

        $view->setEventManager($eventManager);

        $serviceManager = new ServiceManager();
        $serviceManager->setService('Laminas\ApiTools\Hal\JsonStrategy', $strategy);
        $serviceManager->setService('View', $view);

        $application = $this->prophesize(ApplicationInterface::class);
        $application->getServiceManager()->willReturn($serviceManager);

        $mvcEvent = $this->prophesize(MvcEvent::class);
        $mvcEvent->getResult()->willReturn(new HalJsonModel());
        $mvcEvent->getTarget()->willReturn($application->reveal());

        $this->module->onRender($mvcEvent->reveal());
    }
}
