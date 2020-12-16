<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;

class HalJsonRendererFactory
{
    /**
     * @param ContainerInterface $container
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
