<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Link\LinkCollection;

interface LinkCollectionExtractorInterface
{
    /**
     * Extract a link collection into a structured set of links.
     *
     * @return array
     */
    public function extract(LinkCollection $collection);

    /**
     * @return LinkExtractorInterface
     */
    public function getLinkExtractor();
}
