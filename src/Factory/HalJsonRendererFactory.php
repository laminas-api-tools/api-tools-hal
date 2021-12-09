<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;

class HalJsonRendererFactory
{
    /**
     * @return HalJsonRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        $helpers            = $container->get('ViewHelperManager');
        $apiProblemRenderer = $container->get(ApiProblemRenderer::class);

        $renderer = new HalJsonRenderer($apiProblemRenderer);
        $renderer->setHelperPluginManager($helpers);

        return $renderer;
    }
}
