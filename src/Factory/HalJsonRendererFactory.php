<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\View\HelperPluginManager;

class HalJsonRendererFactory
{
    /**
     * @return HalJsonRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        /** @var HelperPluginManager $helpers */
        $helpers = $container->get('ViewHelperManager');
        /** @var ApiProblemRenderer $apiProblemRenderer */
        $apiProblemRenderer = $container->get(ApiProblemRenderer::class);

        $renderer = new HalJsonRenderer($apiProblemRenderer);
        $renderer->setHelperPluginManager($helpers);

        return $renderer;
    }
}
