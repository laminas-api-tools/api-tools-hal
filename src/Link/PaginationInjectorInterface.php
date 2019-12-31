<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Hal\Collection;

interface PaginationInjectorInterface
{
    /**
     * Generate HAL links for a paginated collection
     *
     * @param  Collection $halCollection
     * @return bool|ApiProblem
     */
    public function injectPaginationLinks(Collection $halCollection);
}
