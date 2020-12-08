<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Metadata;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MetadataMapFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Metadata\MetadataMap
    {
        $config = $container->get('Laminas\ApiTools\Hal\HalConfig');

        $hydrators = $container->has('HydratorManager')
            ? $container->get('HydratorManager')
            : new HydratorPluginManager($container);

        $map = (isset($config['metadata_map']) && \is_array($config['metadata_map']))
            ? $config['metadata_map']
            : [];

        return new Metadata\MetadataMap($map, $hydrators);
    }
}
