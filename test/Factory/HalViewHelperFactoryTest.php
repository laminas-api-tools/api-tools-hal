<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\HalViewHelperFactory;
use Laminas\ApiTools\Hal\Plugin;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Hydrator\HydratorPluginManager;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionObject;

class HalViewHelperFactoryTest extends TestCase
{
    public function setupPluginManager($config = [])
    {
        $services = new ServiceManager();

        $services->setService('Config', $config);

        $metadataMap = $this->getMock('Laminas\ApiTools\Hal\Metadata\MetadataMap');
        $metadataMap
            ->expects($this->once())
            ->method('getHydratorManager')
            ->will($this->returnValue(new HydratorPluginManager()));

        $services->setService('Laminas\ApiTools\Hal\MetadataMap', $metadataMap);

        $this->pluginManager = $this->getMock('Laminas\ServiceManager\AbstractPluginManager');

        $this->pluginManager
            ->expects($this->at(1))
            ->method('get')
            ->with('ServerUrl')
            ->will($this->returnValue(new ServerUrl()));

        $this->pluginManager
            ->expects($this->at(2))
            ->method('get')
            ->with('Url')
            ->will($this->returnValue(new Url()));

        $this->pluginManager
            ->expects($this->any())
            ->method('getServiceLocator')
            ->will($this->returnValue($services));
    }

    public function testInstantiatesHalViewHelper()
    {
        $this->setupPluginManager();

        $factory = new HalViewHelperFactory();
        $plugin = $factory->createService($this->pluginManager);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Plugin\Hal', $plugin);
    }

    public function testHalViewHelperFactoryInjectsDefaultHydratorIfPresentInConfig()
    {
        $config = [
            'api-tools-hal' => [
                'renderer' => [
                    'default_hydrator' => 'ObjectProperty',
                ],
            ],
        ];

        $this->setupPluginManager($config);

        $factory = new HalViewHelperFactory();
        $plugin = $factory->createService($this->pluginManager);

        $this->assertInstanceOf('Laminas\ApiTools\Hal\Plugin\Hal', $plugin);
        $this->assertAttributeInstanceOf('Laminas\Stdlib\Hydrator\ObjectProperty', 'defaultHydrator', $plugin);
    }

    public function testHalViewHelperFactoryInjectsHydratorMappingsIfPresentInConfig()
    {
        $config = [
            'api-tools-hal' => [
                'renderer' => [
                    'hydrators' => [
                        'Some\MadeUp\Component'            => 'ClassMethods',
                        'Another\MadeUp\Component'         => 'Reflection',
                        'StillAnother\MadeUp\Component'    => 'ArraySerializable',
                        'A\Component\With\SharedHydrators' => 'Reflection',
                    ],
                ],
            ],
        ];

        $this->setupPluginManager($config);

        $factory = new HalViewHelperFactory();
        $plugin = $factory->createService($this->pluginManager);

        $r             = new ReflectionObject($plugin);
        $hydratorsProp = $r->getProperty('hydratorMap');
        $hydratorsProp->setAccessible(true);
        $hydratorMap = $hydratorsProp->getValue($plugin);

        $hydrators = $plugin->getHydratorManager();

        $this->assertInternalType('array', $hydratorMap);

        foreach ($config['api-tools-hal']['renderer']['hydrators'] as $class => $serviceName) {
            $key = strtolower($class);
            $this->assertArrayHasKey($key, $hydratorMap);

            $hydrator = $hydratorMap[$key];
            $this->assertSame(get_class($hydrators->get($serviceName)), get_class($hydrator));
        }
    }

    /**
     * @group fail
     */
    public function testOptionUseProxyIfPresentInConfig()
    {
        $options = [
            'api-tools-hal' => [
                'options' => [
                    'use_proxy' => true,
                ],
            ],
        ];

        $this->setupPluginManager($options);

        $factory = new HalViewHelperFactory();
        $halPlugin = $factory->createService($this->pluginManager);

        $r = new ReflectionObject($halPlugin);
        $p = $r->getProperty('serverUrlHelper');
        $p->setAccessible(true);
        $serverUrlPlugin = $p->getValue($halPlugin);
        $this->assertInstanceOf('Laminas\View\Helper\ServerUrl', $serverUrlPlugin);

        $r = new ReflectionObject($serverUrlPlugin);
        $p = $r->getProperty('useProxy');
        $p->setAccessible(true);
        $useProxy = $p->getValue($serverUrlPlugin);
        $this->assertInternalType('boolean', $useProxy);
        $this->assertTrue($useProxy);
    }
}
