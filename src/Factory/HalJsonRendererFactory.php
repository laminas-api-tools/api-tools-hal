<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

// phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
use Laminas\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Laminas\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\View\HelperPluginManager;

use function assert;

class HalJsonRendererFactory
{
    /**
     * @return HalJsonRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        $helpers = $container->get('ViewHelperManager');
        assert($helpers instanceof HelperPluginManager);
        $apiProblemRenderer = $container->get(ApiProblemRenderer::class);
        assert($apiProblemRenderer instanceof ApiProblemRenderer);

        $renderer = new HalJsonRenderer($apiProblemRenderer);
        $renderer->setHelperPluginManager($helpers);

        return $renderer;
    }
}
