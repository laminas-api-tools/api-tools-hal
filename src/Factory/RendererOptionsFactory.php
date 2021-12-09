<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ApiTools\Hal\RendererOptions;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

use function is_array;

class RendererOptionsFactory
{
    /**
     * @return RendererOptions
     * @throws ServiceNotFoundException If unable to resolve the service.
     * @throws ServiceNotCreatedException If an exception is raised when
     *     creating a service.
     * @throws ContainerException If any other error occurs.
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('Laminas\ApiTools\Hal\HalConfig');

        $rendererConfig = isset($config['renderer']) && is_array($config['renderer'])
            ? $config['renderer']
            : [];

        if (
            isset($rendererConfig['render_embedded_resources'])
            && ! isset($rendererConfig['render_embedded_entities'])
        ) {
            $rendererConfig['render_embedded_entities'] = $rendererConfig['render_embedded_resources'];
        }

        return new RendererOptions($rendererConfig);
    }
}
