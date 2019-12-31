<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Hydrator\HydratorInterface;
use Laminas\Stdlib\Hydrator\HydratorPluginManager;

/**
 * Laminas module
 */
class Module
{
    /**
     * Retrieve autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array('Laminas\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/',
        )));
    }

    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Retrieve Service Manager configuration
     *
     * Defines Laminas\ApiTools\Hal\JsonStrategy service factory.
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array('factories' => array(
            'Laminas\ApiTools\Hal\MetadataMap' => function ($services) {
                $config = array();
                if ($services->has('config')) {
                    $config = $services->get('config');
                }

                if ($services->has('HydratorManager')) {
                    $hydrators = $services->get('HydratorManager');
                } else {
                    $hydrators = new HydratorPluginManager();
                }

                $map = array();
                if (isset($config['api-tools-hal'])
                    && isset($config['api-tools-hal']['metadata_map'])
                    && is_array($config['api-tools-hal']['metadata_map'])
                ) {
                    $map = $config['api-tools-hal']['metadata_map'];
                }

                return new Metadata\MetadataMap($map, $hydrators);
            },
            'Laminas\ApiTools\Hal\JsonRenderer' => function ($services) {
                $helpers            = $services->get('ViewHelperManager');
                $apiProblemRenderer = $services->get('Laminas\ApiTools\ApiProblem\ApiProblemRenderer');
                $config             = $services->get('Config');

                $renderer = new View\HalJsonRenderer($apiProblemRenderer);
                $renderer->setHelperPluginManager($helpers);

                return $renderer;
            },
            'Laminas\ApiTools\Hal\JsonStrategy' => function ($services) {
                $renderer = $services->get('Laminas\ApiTools\Hal\JsonRenderer');
                return new View\HalJsonStrategy($renderer);
            },
        ));
    }

    /**
     * Define factories for controller plugins
     *
     * Defines the "Hal" plugin.
     *
     * @return array
     */
    public function getControllerPluginConfig()
    {
        return array('factories' => array(
            'Hal' => function ($plugins) {
                $services = $plugins->getServiceLocator();
                $helpers  = $services->get('ViewHelperManager');
                return $helpers->get('Hal');
            },
        ));
    }

    /**
     * Defines the "Hal" view helper
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return array('factories' => array(
            'Hal' => function ($helpers) {
                $serverUrlHelper = $helpers->get('ServerUrl');
                $urlHelper       = $helpers->get('Url');

                $services        = $helpers->getServiceLocator();
                $config          = $services->get('Config');
                $metadataMap     = $services->get('Laminas\ApiTools\Hal\MetadataMap');
                $hydrators       = $metadataMap->getHydratorManager();

                $helper          = new Plugin\Hal($hydrators);
                $helper->setMetadataMap($metadataMap);
                $helper->setServerUrlHelper($serverUrlHelper);
                $helper->setUrlHelper($urlHelper);

                if (isset($config['api-tools-hal'])
                    && isset($config['api-tools-hal']['renderer'])
                ) {
                    $config = $config['api-tools-hal']['renderer'];

                    if (isset($config['default_hydrator'])) {
                        $hydratorServiceName = $config['default_hydrator'];

                        if (!$hydrators->has($hydratorServiceName)) {
                            throw new Exception\DomainException(
                                sprintf(
                                    'Cannot locate default hydrator by name "%s" via the HydratorManager',
                                    $hydratorServiceName
                                )
                            );
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
        ));
    }

    /**
     * Listener for bootstrap event
     *
     * Attaches a render event.
     *
     * @param  \Laminas\Mvc\MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $app      = $e->getTarget();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $events->attach(MvcEvent::EVENT_RENDER, array($this, 'onRender'), 100);
    }

    /**
     * Listener for the render event
     *
     * Attaches a rendering/response strategy to the View.
     *
     * @param  \Laminas\Mvc\MvcEvent $e
     */
    public function onRender($e)
    {
        $result = $e->getResult();
        if (!$result instanceof View\HalJsonModel) {
            return;
        }

        $app                 = $e->getTarget();
        $services            = $app->getServiceManager();
        $view                = $services->get('View');
        $events              = $view->getEventManager();

        // register at high priority, to "beat" normal json strategy registered
        // via view manager
        $events->attach($services->get('Laminas\ApiTools\Hal\JsonStrategy'), 200);
    }
}
