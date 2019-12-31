<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Factory\HalViewHelperFactory;
use Laminas\ApiTools\Hal\Link;
use Laminas\ApiTools\Hal\RendererOptions;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;

class HalViewHelperFactoryTest extends TestCase
{
    private $pluginManager;
    private $services;

    public function setupPluginManager($config = [])
    {
        $services = new ServiceManager();

        $services->setService('Laminas\ApiTools\Hal\HalConfig', $config);

        if (isset($config['renderer']) && is_array($config['renderer'])) {
            $rendererOptions = new RendererOptions($config['renderer']);
        } else {
            $rendererOptions = new RendererOptions();
        }
        $services->setService(RendererOptions::class, $rendererOptions);

        $metadataMap = $this->getMockBuilder('Laminas\ApiTools\Hal\Metadata\MetadataMap')->getMock();
        $metadataMap
            ->expects($this->once())
            ->method('getHydratorManager')
            ->will($this->returnValue(new HydratorPluginManager($services)));
        $services->setService('Laminas\ApiTools\Hal\MetadataMap', $metadataMap);

        $linkUrlBuilder = $this->getMockBuilder(Link\LinkUrlBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $services->setService(Link\LinkUrlBuilder::class, $linkUrlBuilder);

        $linkCollectionExtractor = $this->getMockBuilder(LinkCollectionExtractor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $services->setService(LinkCollectionExtractor::class, $linkCollectionExtractor);

        $pluginManagerMock = $this->getMockBuilder(AbstractPluginManager::class);
        $pluginManagerMock->setConstructorArgs([$services]);
        $this->pluginManager = $pluginManagerMock->getMock();
        $services->setService('ViewHelperManager', $this->pluginManager);

        $this->services = $services;
    }

    public function testInstantiatesHalViewHelper()
    {
        $this->setupPluginManager();

        $factory = new HalViewHelperFactory();
        $plugin = $factory($this->services, 'Hal');

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Plugin\Hal', $plugin);
    }
}
