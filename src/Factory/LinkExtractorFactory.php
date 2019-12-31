<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Factory;

use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;

class LinkExtractorFactory
{
    /**
     * @param  \Interop\Container\ContainerInterface|\Laminas\ServiceManager\ServiceLocatorInterface $container
     * @return LinkExtractor
     */
    public function __invoke($container)
    {
        return new LinkExtractor($container->get(LinkUrlBuilder::class));
    }
}
