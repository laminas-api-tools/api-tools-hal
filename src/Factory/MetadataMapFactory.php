<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Metadata;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\Hydrator\HydratorPluginManager;

class MetadataMapFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return Metadata\MetadataMap
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = [];
        if ($serviceLocator->has('config')) {
            $config = $serviceLocator->get('config');
        }

        if ($serviceLocator->has('HydratorManager')) {
            $hydrators = $serviceLocator->get('HydratorManager');
        } else {
            $hydrators = new HydratorPluginManager();
        }

        $map = [];
        if (isset($config['api-tools-hal'])
            && isset($config['api-tools-hal']['metadata_map'])
            && is_array($config['api-tools-hal']['metadata_map'])
        ) {
            $map = $config['api-tools-hal']['metadata_map'];
        }

        return new Metadata\MetadataMap($map, $hydrators);
    }
}
