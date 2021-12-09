<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\ServiceLocatorInterface;

class LinkExtractorFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return LinkExtractor
     */
    public function __invoke($container)
    {
        return new LinkExtractor($container->get(LinkUrlBuilder::class));
    }
}
