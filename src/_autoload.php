<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

use Laminas\Hydrator\HydratorPluginManagerInterface;

/**
 * Alias Laminas\ApiTools\Hal\Extractor\EntityExtractor to the appropriate class based on
 * which version of laminas-hydrator we detect. HydratorPluginManagerInterface
 * is added in v3.
 */
if (interface_exists(HydratorPluginManagerInterface::class, true)) {
    class_alias(Extractor\EntityExtractorHydratorV3::class, Extractor\EntityExtractor::class, true);
} else {
    class_alias(Extractor\EntityExtractorHydratorV2::class, Extractor\EntityExtractor::class, true);
}
