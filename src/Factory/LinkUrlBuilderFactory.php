<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Helper\ServerUrl;

class LinkUrlBuilderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): LinkUrlBuilder
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
