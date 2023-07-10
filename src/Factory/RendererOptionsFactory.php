<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

// phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\RendererOptions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Traversable;

use function is_array;

class RendererOptionsFactory
{
    /**
     * @return RendererOptions
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
