<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
// phpcs:enable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Laminas\ApiTools\Hal\RendererOptions;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Traversable;

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

        /** @psalm-var Traversable|array<array-key, mixed>|null $rendererConfig */
        return new RendererOptions($rendererConfig);
    }
}
