<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\RendererOptions;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class RendererOptionsFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return RendererOptions
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Laminas\ApiTools\Hal\HalConfig');

        $rendererConfig = [];
        if (isset($config['renderer']) && is_array($config['renderer'])) {
            $rendererConfig = $config['renderer'];
        }

        if (isset($rendererConfig['render_embedded_resources'])
            && !isset($rendererConfig['render_embedded_entities'])
        ) {
            $rendererConfig['render_embedded_entities'] = $rendererConfig['render_embedded_resources'];
        }

        return new RendererOptions($rendererConfig);
    }
}
