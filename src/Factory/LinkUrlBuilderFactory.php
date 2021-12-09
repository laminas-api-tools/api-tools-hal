<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\ServerUrl;

class LinkUrlBuilderFactory
{
    /**
     * @param  ContainerInterface|ServiceLocatorInterface $container
     * @return LinkUrlBuilder
     */
    public function __invoke($container)
    {
        $halConfig = $container->get('Laminas\ApiTools\Hal\HalConfig');

        $viewHelperManager = $container->get('ViewHelperManager');

        /** @var ServerUrl $serverUrlHelper */
        $serverUrlHelper = $viewHelperManager->get('ServerUrl');
        if (isset($halConfig['options']['use_proxy'])) {
            $serverUrlHelper->setUseProxy($halConfig['options']['use_proxy']);
        }

        $urlHelper = $viewHelperManager->get('Url');

        return new LinkUrlBuilder($serverUrlHelper, $urlHelper);
    }
}
