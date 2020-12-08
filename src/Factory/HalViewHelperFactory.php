<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Exception;
use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Link;
use Laminas\ApiTools\Hal\Plugin;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class HalViewHelperFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): Plugin\Hal
    {
        $container = ($container instanceof AbstractPluginManager)
            ? $container->getServiceLocator()
            : $container;

        /* @var $rendererOptions \Laminas\ApiTools\Hal\RendererOptions */
        $rendererOptions = $container->get('Laminas\ApiTools\Hal\RendererOptions');
        $metadataMap     = $container->get('Laminas\ApiTools\Hal\MetadataMap');

        /** @var HydratorPluginManager $hydrators */
        $hydrators       = $metadataMap->getHydratorManager();

        $helper = new Plugin\Hal($hydrators);

        if ($container->has('EventManager')) {
            $helper->setEventManager($container->get('EventManager'));
        }

        $helper->setMetadataMap($metadataMap);

        $linkUrlBuilder = $container->get(Link\LinkUrlBuilder::class);
        $helper->setLinkUrlBuilder($linkUrlBuilder);

        $linkCollectionExtractor = $container->get(LinkCollectionExtractor::class);
        $helper->setLinkCollectionExtractor($linkCollectionExtractor);

        $defaultHydrator = $rendererOptions->getDefaultHydrator();
        if ($defaultHydrator) {
            if (! $hydrators->has($defaultHydrator)) {
                throw new Exception\DomainException(sprintf(
                    'Cannot locate default hydrator by name "%s" via the HydratorManager',
                    $defaultHydrator
                ));
            }

            /** @var HydratorInterface $hydrator */
            $hydrator = $hydrators->get($defaultHydrator);
            $helper->setDefaultHydrator($hydrator);
        }

        $helper->setRenderEmbeddedEntities($rendererOptions->getRenderEmbeddedEntities());
        $helper->setRenderCollections($rendererOptions->getRenderEmbeddedCollections());

        $hydratorMap = $rendererOptions->getHydrators();
        foreach ($hydratorMap as $class => $hydratorServiceName) {
            $helper->addHydrator($class, $hydratorServiceName);
        }

        return $helper;
    }

    /**
     * Proxies to __invoke() to provide backwards compatibility.
     *
     * @deprecated since 1.4.0; use __invoke instead.
     * @param  ServiceLocatorInterface $container
     * @return Plugin\Hal
     */
    public function createService($container): Plugin\Hal
    {
        return $this($container, Plugin\Hal::class);
    }
}
