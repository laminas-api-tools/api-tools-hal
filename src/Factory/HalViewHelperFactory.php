<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
// phpcs:enable WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Laminas\ApiTools\Hal\Exception;
use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Link;
use Laminas\ApiTools\Hal\Metadata\MetadataMap;
use Laminas\ApiTools\Hal\Plugin;
use Laminas\ApiTools\Hal\RendererOptions;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceLocatorInterface;

use function sprintf;

class HalViewHelperFactory
{
    /**
     * @param  ContainerInterface|ServiceLocatorInterface $container
     * @return Plugin\Hal
     */
    public function __invoke(ContainerInterface $container)
    {
        $container = $container instanceof AbstractPluginManager
            ? $container->getServiceLocator()
            : $container;

        /** @var RendererOptions $rendererOptions */
        $rendererOptions = $container->get(RendererOptions::class);
        /** @var MetadataMap $metadataMap */
        $metadataMap = $container->get('Laminas\ApiTools\Hal\MetadataMap');

        /** @var HydratorPluginManager $hydrators */
        $hydrators = $metadataMap->getHydratorManager();

        $helper = new Plugin\Hal($hydrators);

        if ($container->has('EventManager')) {
            /** @var EventManagerInterface $eventManager */
            $eventManager = $container->get('EventManager');
            $helper->setEventManager($eventManager);
        }

        $helper->setMetadataMap($metadataMap);
        /** @var Link\LinkUrlBuilder $linkUrlBuilder */
        $linkUrlBuilder = $container->get(Link\LinkUrlBuilder::class);
        $helper->setLinkUrlBuilder($linkUrlBuilder);

        /** @var LinkCollectionExtractor $linkCollectionExtractor */
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
     *
     * @param  ServiceLocatorInterface $container
     * @return Plugin\Hal
     */
    public function createService($container)
    {
        return $this($container);
    }
}
