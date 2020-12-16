<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;

class HalJsonStrategyFactory
{
    /**
     * @param ContainerInterface $container
     * @return HalJsonStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        return new HalJsonStrategy($container->get('Laminas\ApiTools\Hal\JsonRenderer'));
    }
}
