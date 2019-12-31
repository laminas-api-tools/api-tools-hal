<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

use Laminas\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;

class Module
{
    /**
     * Retrieve module configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Listener for bootstrap event
     *
     * Attaches a render event.
     *
     * @param  MvcEvent $e
     */
    public function onBootstrap(MvcEvent $e)
    {
        /** @var ApplicationInterface $application */
        $application = $e->getTarget();
        $events = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_RENDER, [$this, 'onRender'], 100);
    }

    /**
     * Listener for the render event
     *
     * Attaches a rendering/response strategy to the View.
     *
     * @param  MvcEvent $e
     */
    public function onRender(MvcEvent $e)
    {
        $result = $e->getResult();
        if (! $result instanceof View\HalJsonModel) {
            return;
        }

        /** @var Application $application */
        $application = $e->getTarget();
        $services = $application->getServiceManager();
        $events   = $services->get('View')->getEventManager();

        // register at high priority, to "beat" normal json strategy registered
        // via view manager
        /** @var HalJsonStrategy $halStrategy */
        $halStrategy = $services->get('Laminas\ApiTools\Hal\JsonStrategy');
        $halStrategy->attach($events, 200);
    }
}
