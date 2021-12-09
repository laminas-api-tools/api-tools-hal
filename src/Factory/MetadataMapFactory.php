<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Laminas\ApiTools\Hal\Metadata;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

use function is_array;

class MetadataMapFactory
{
    /**
     * Create an object
     *
     * @return Metadata\MetadataMap
     * @throws ServiceNotFoundException If unable to resolve the service.
     * @throws ServiceNotCreatedException If an exception is raised when
     *     creating a service.
     * @throws ContainerException If any other error occurs.
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
