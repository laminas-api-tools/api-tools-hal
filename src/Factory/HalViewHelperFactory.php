<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Exception;
use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Plugin;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class HalViewHelperFactory implements FactoryInterface
{
    /**
     * @param  ServiceLocatorInterface $serviceLocator
     * @return Plugin\Hal
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $services        = $serviceLocator->getServiceLocator();
        $config          = $services->get('Config');
        $metadataMap     = $services->get('Laminas\ApiTools\Hal\MetadataMap');
        $hydrators       = $metadataMap->getHydratorManager();

        $serverUrlHelper = $serviceLocator->get('ServerUrl');
        if (isset($config['api-tools-hal']['options']['use_proxy'])) {
            $serverUrlHelper->setUseProxy($config['api-tools-hal']['options']['use_proxy']);
        }
        $urlHelper       = $serviceLocator->get('Url');

        $helper = new Plugin\Hal($hydrators);
        $helper
            ->setMetadataMap($metadataMap)
            ->setServerUrlHelper($serverUrlHelper)
            ->setUrlHelper($urlHelper);

        $linkExtractor = new LinkExtractor($serverUrlHelper, $urlHelper);
        $linkCollectionExtractor = new LinkCollectionExtractor($linkExtractor);
        $helper->setLinkCollectionExtractor($linkCollectionExtractor);

        if (isset($config['api-tools-hal'])
            && isset($config['api-tools-hal']['renderer'])
        ) {
            $config = $config['api-tools-hal']['renderer'];

            if (isset($config['default_hydrator'])) {
                $hydratorServiceName = $config['default_hydrator'];

                if (!$hydrators->has($hydratorServiceName)) {
                    throw new Exception\DomainException(sprintf(
                        'Cannot locate default hydrator by name "%s" via the HydratorManager',
                        $hydratorServiceName
                    ));
                }

                $hydrator = $hydrators->get($hydratorServiceName);
                $helper->setDefaultHydrator($hydrator);
            }

            if (isset($config['render_embedded_resources'])) {
                $helper->setRenderEmbeddedEntities($config['render_embedded_resources']);
            }

            if (isset($config['render_embedded_entities'])) {
                $helper->setRenderEmbeddedEntities($config['render_embedded_entities']);
            }

            if (isset($config['render_collections'])) {
                $helper->setRenderCollections($config['render_collections']);
            }

            if (isset($config['hydrators']) && is_array($config['hydrators'])) {
                $hydratorMap = $config['hydrators'];
                foreach ($hydratorMap as $class => $hydratorServiceName) {
                    $helper->addHydrator($class, $hydratorServiceName);
                }
            }
        }

        return $helper;
    }
}
