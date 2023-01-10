<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;

class HalJsonStrategyFactory
{
    /**
     * @return HalJsonStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        /** @var HalJsonRenderer $renderer */
        $renderer = $container->get('Laminas\ApiTools\Hal\JsonRenderer');

        return new HalJsonStrategy($renderer);
    }
}
