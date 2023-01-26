<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

// phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;

use function assert;

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

        $serverUrlHelper = $viewHelperManager->get('ServerUrl');
        assert($serverUrlHelper instanceof ServerUrl);

        if (isset($halConfig['options']['use_proxy'])) {
            $serverUrlHelper->setUseProxy($halConfig['options']['use_proxy']);
        }

        $urlHelper = $viewHelperManager->get('Url');
        assert($urlHelper instanceof Url);

        return new LinkUrlBuilder($serverUrlHelper, $urlHelper);
    }
}
