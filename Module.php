<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

use Laminas\Mvc\MvcEvent;

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
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
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
     * Listener for bootstrap event
     *
     * Attaches a render event.
     *
     * @param  \Laminas\Mvc\MvcEvent $e
     */
    public function onBootstrap($e)
    {
        $events = $e->getTarget()->getEventManager();
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

        $services = $e->getTarget()->getServiceManager();
        $events   = $services->get('View')->getEventManager();

        // register at high priority, to "beat" normal json strategy registered
        // via view manager
        $events->attach($services->get('Laminas\ApiTools\Hal\JsonStrategy'), 200);
    }
}
