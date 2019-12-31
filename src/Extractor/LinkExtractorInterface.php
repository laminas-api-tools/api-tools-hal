<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Link\Link;

interface LinkExtractorInterface
{
    /**
     * Extract a structured link array from a Link instance.
     *
     * @param Link $link
     * @return array
     */
    public function extract(Link $link);
}
