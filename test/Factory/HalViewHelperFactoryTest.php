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
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\ApiTools\Hal\Plugin\Hal as HalPlugin;
use Laminas\ApiTools\Hal\RendererOptions;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class HalViewHelperFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var AbstractPluginManager
     */
    private $pluginManager;
    /**
     * @var ServiceManager
     */
    private $services;

    public function setupPluginManager($config = []): void
    {
        $services = new ServiceManager();

        $services->setService('Laminas\ApiTools\Hal\HalConfig', $config);

        if (isset($config['renderer']) && is_array($config['renderer'])) {
            $rendererOptions = new RendererOptions($config['renderer']);
        } else {
            $rendererOptions = new RendererOptions();
        }
        $services->setService(RendererOptions::class, $rendererOptions);

        $metadataMap = $this->prophesize(MetadataMap::class);
        $metadataMap->getHydratorManager()->willReturn(new HydratorPluginManager($services))->shouldBeCalledTimes(1);
        $services->setService('Laminas\ApiTools\Hal\MetadataMap', $metadataMap->reveal());

        $linkUrlBuilder = $this->createMock(Link\LinkUrlBuilder::class);
        $services->setService(Link\LinkUrlBuilder::class, $linkUrlBuilder);

        $linkCollectionExtractor = $this->createMock(LinkCollectionExtractor::class);
        $services->setService(LinkCollectionExtractor::class, $linkCollectionExtractor);

        $this->pluginManager = $this->getMockForAbstractClass(AbstractPluginManager::class, [$services]);

        $services->setService('ViewHelperManager', $this->pluginManager);

        $this->services = $services;
    }

    public function testInstantiatesHalViewHelper(): void
    {
        $this->setupPluginManager();

        $sharedEventManager = $this->getMockBuilder(SharedEventManagerInterface::class)
            ->getMock();
        $eventManagerMock = $this->getMockBuilder(EventManagerInterface::class)
            ->getMock();
        $eventManagerMock->method('getSharedManager')->willReturn($sharedEventManager);

        $this->services->setService('EventManager', $eventManagerMock);

        $factory = new HalViewHelperFactory();
        $plugin = $factory($this->services, HalPlugin::class);

        self::assertInstanceOf(SharedEventManagerInterface::class, $plugin->getEventManager()->getSharedManager());
    }
}
