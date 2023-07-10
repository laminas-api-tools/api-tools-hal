<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal;

use Laminas\ApiTools\Hal\View\HalJsonModel;
use Laminas\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\EventManager\EventInterface;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\View\View;

class Module implements BootstrapListenerInterface, ConfigProviderInterface
{
    public function getConfig(): array
    {
        /** @psalm-var array */
        return include __DIR__ . '/../config/module.config.php';
    }

    public function onBootstrap(EventInterface $e): void
    {
        /** @var ApplicationInterface $application */
        $application = $e->getTarget();
        $events      = $application->getEventManager();

        $events->attach(MvcEvent::EVENT_RENDER, [$this, 'onRender'], 100);
    }

    /**
     * Listener for the render event
     *
     * Attaches a rendering/response strategy to the View.
     */
    public function onRender(MvcEvent $e): void
    {
        $result = $e->getResult();
        if (! $result instanceof HalJsonModel) {
            return;
        }

        /** @var ApplicationInterface $application */
        $application = $e->getTarget();
        $services    = $application->getServiceManager();
        /** @var View $view */
        $view   = $services->get('View');
        $events = $view->getEventManager();

        // register at high priority, to "beat" normal json strategy registered
        // via view manager
        /** @var HalJsonStrategy $halStrategy */
        $halStrategy = $services->get('Laminas\ApiTools\Hal\JsonStrategy');
        $halStrategy->attach($events, 200);
    }
}
