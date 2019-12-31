<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Link\LinkCollection;

interface LinkCollectionExtractorInterface
{
    /**
     * Extract a link collection into a structured set of links.
     *
     * @param LinkCollection $collection
     * @return array
     */
    public function extract(LinkCollection $collection);

    /**
     * @return LinkExtractorInterface
     */
    public function getLinkExtractor();
}
