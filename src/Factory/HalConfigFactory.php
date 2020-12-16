<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;

class HalConfigFactory
{
    /**
     * @param ContainerInterface $container
     * @return array|\ArrayAccess
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];

        return (isset($config['api-tools-hal']) && is_array($config['api-tools-hal']))
            ? $config['api-tools-hal']
            : [];
    }
}
