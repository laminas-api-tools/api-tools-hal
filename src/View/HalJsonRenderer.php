<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\View;

use ArrayAccess;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\View\ApiProblemModel;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\Collection;
use Laminas\ApiTools\Hal\Entity;
use Laminas\ApiTools\Hal\Plugin\Hal;
use Laminas\View\HelperPluginManager;
use Laminas\View\Model\ModelInterface;
use Laminas\View\Renderer\JsonRenderer;
use Laminas\View\ViewEvent;

/**
 * Handles rendering of the following:
 *
 * - API-Problem
 * - HAL collections
 * - HAL resources
 */
class HalJsonRenderer extends JsonRenderer
{
    /** @var ApiProblemRenderer */
    protected $apiProblemRenderer;

    /** @var HelperPluginManager|null */
    protected $helpers;

    /** @var ViewEvent|null */
    protected $viewEvent;

    public function __construct(ApiProblemRenderer $apiProblemRenderer)
    {
        $this->apiProblemRenderer = $apiProblemRenderer;
    }

    /**
     * Set helper plugin manager instance.
     *
     * Also ensures that the 'Hal' helper is present.
     *
     * @return void
     */
    public function setHelperPluginManager(HelperPluginManager $helpers)
    {
        $this->helpers = $helpers;
    }

    /**
     * @return self
     */
    public function setViewEvent(ViewEvent $event)
    {
        $this->viewEvent = $event;
        return $this;
    }

    /**
     * Lazy-loads a helper plugin manager if none available.
     *
     * @return HelperPluginManager
     */
    public function getHelperPluginManager()
    {
        if (! $this->helpers instanceof HelperPluginManager) {
            $this->setHelperPluginManager($helpers = new HelperPluginManager());
            return $helpers;
        }

        return $this->helpers;
    }

    /**
     * @return ViewEvent|null
     */
    public function getViewEvent()
    {
        return $this->viewEvent;
    }

    /**
     * Render a view model
     *
     * If the view model is a HalJsonRenderer, determines if it represents
     * a Collection or Entity, and, if so, creates a custom
     * representation appropriate to the type.
     *
     * If not, it passes control to the parent to render.
     *
     * @param  mixed $nameOrModel
     * @param  null|array|ArrayAccess $values
     * @return string
     */
    public function render($nameOrModel, $values = null)
    {
        if (! $nameOrModel instanceof HalJsonModel) {
            /** @psalm-var ModelInterface|string $nameOrModel */
            return parent::render($nameOrModel, $values);
        }

        if ($nameOrModel->isEntity()) {
            /** @psalm-var Hal $helper */
            $helper = $this->getHelperPluginManager()->get('Hal');
            /** @psalm-var Entity $entity */
            $entity  = $nameOrModel->getPayload();
            $payload = $helper->renderEntity($entity);
            return parent::render($payload);
        }

        if ($nameOrModel->isCollection()) {
            /** @var Hal $helper */
            $helper = $this->getHelperPluginManager()->get('Hal');
            /** @var Collection $collection */
            $collection = $nameOrModel->getPayload();
            $payload    = $helper->renderCollection($collection);

            if ($payload instanceof ApiProblem) {
                return $this->renderApiProblem($payload);
            }
            /** @psalm-suppress InvalidArgument to be discussed */
            return parent::render($payload);
        }

        return parent::render($nameOrModel, $values);
    }

    /**
     * Render an API-Problem result
     *
     * Creates an ApiProblemModel with the provided ApiProblem, and passes it
     * on to the composed ApiProblemRenderer to render.
     *
     * If a ViewEvent is composed, it passes the ApiProblemModel to it so that
     * the ApiProblemStrategy can be invoked when populating the response.
     *
     * @return string
     */
    protected function renderApiProblem(ApiProblem $problem)
    {
        $model = new ApiProblemModel($problem);
        $event = $this->getViewEvent();
        if ($event instanceof ViewEvent) {
            $event->setModel($model);
        }

        return $this->apiProblemRenderer->render($model);
    }
}
