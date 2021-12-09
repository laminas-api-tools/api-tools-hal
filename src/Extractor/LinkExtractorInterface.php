<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\Hal\Link\Link;

interface LinkExtractorInterface
{
    /**
     * Extract a structured link array from a Link instance.
     *
     * @return array
     */
    public function extract(Link $link);
}
