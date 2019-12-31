<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\ApiProblem\Factory\ApiProblemRendererFactory;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Factory\HalJsonRendererFactory;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;

class HalJsonRendererFactoryTest extends TestCase
{
    public function testInstantiatesHalJsonRenderer()
    {
        $services = new ServiceManager();

        $viewHelperManager = $this->getMockBuilder('Laminas\View\HelperPluginManager')
            ->disableOriginalConstructor()
            ->getMock();

        $services->setService('ViewHelperManager', $viewHelperManager);

        $services->setInvokableClass(ApiProblemRenderer::class, ApiProblemRenderer::class);

        $factory = new HalJsonRendererFactory();
        $renderer = $factory($services,'Laminas\ApiTools\Hal\JsonRenderer');

        $this->assertInstanceOf('Laminas\ApiTools\Hal\View\HalJsonRenderer', $renderer);
    }
}
