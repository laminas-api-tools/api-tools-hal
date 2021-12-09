<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Plugin\Hal;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class HalControllerPluginFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param null|array $options
     * @return Hal
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $helpers = $container->get('ViewHelperManager');
        return $helpers->get('Hal');
    }

    /**
     * Create service
     *
     * @return Hal
     */
    public function createService(ServiceLocatorInterface $container)
    {
        if ($container instanceof AbstractPluginManager) {
            $container = $container->getServiceLocator() ?: $container;
        }
        return $this($container, Hal::class);
    }
}
