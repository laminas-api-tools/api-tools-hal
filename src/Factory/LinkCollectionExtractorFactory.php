<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Laminas\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ServiceManager\ServiceLocatorInterface;

class LinkCollectionExtractorFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return LinkCollectionExtractor
     */
    public function __invoke($container)
    {
        return new LinkCollectionExtractor($container->get(LinkExtractor::class));
    }
}
