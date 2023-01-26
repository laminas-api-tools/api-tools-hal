<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

// phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Metadata;
use Laminas\Hydrator\HydratorPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function is_array;

class MetadataMapFactory
{
    /**
     * @return Metadata\MetadataMap
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('Laminas\ApiTools\Hal\HalConfig');

        $hydrators = $container->has('HydratorManager')
            ? $container->get('HydratorManager')
            : new HydratorPluginManager($container);

        $map = isset($config['metadata_map']) && is_array($config['metadata_map'])
            ? $config['metadata_map']
            : [];

        return new Metadata\MetadataMap($map, $hydrators);
    }
}
