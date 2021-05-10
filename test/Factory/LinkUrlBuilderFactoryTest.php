<?php

namespace LaminasTest\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Factory\LinkUrlBuilderFactory;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Helper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class LinkUrlBuilderFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var Helper\ServerUrl */
    private $serverUrlHelper;

    public function testInstantiatesLinkUrlBuilder(): void
    {
        $serviceManager = $this->getServiceManager();

        $factory = new LinkUrlBuilderFactory();
        $builder = $factory($serviceManager, LinkUrlBuilder::class);

        self::assertInstanceOf(LinkUrlBuilder::class, $builder);
    }

    public function testOptionUseProxyIfPresentInConfig(): void
    {
        $options        = [
            'options' => [
                'use_proxy' => true,
            ],
        ];
        $serviceManager = $this->getServiceManager($options);

        $this->serverUrlHelper
            ->setUseProxy($options['options']['use_proxy'])
            ->shouldBeCalled();

        $factory = new LinkUrlBuilderFactory();
        $factory($serviceManager, LinkUrlBuilder::class);
    }

    /**
     * @param array $config
     */
    private function getServiceManager($config = []): ServiceManager
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('Laminas\ApiTools\Hal\HalConfig', $config);

        $viewHelperManager = new ServiceManager();
        $serviceManager->setService('ViewHelperManager', $viewHelperManager);

        $this->serverUrlHelper = $serverUrlHelper = $this->prophesize(Helper\ServerUrl::class);
        $viewHelperManager->setService('ServerUrl', $serverUrlHelper->reveal());

        $urlHelper = $this->prophesize(Helper\Url::class);
        $viewHelperManager->setService('Url', $urlHelper->reveal());

        return $serviceManager;
    }
}
