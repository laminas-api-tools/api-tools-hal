<?php

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\Hal\Collection;

interface PaginationInjectorInterface
{
    /**
     * Generate HAL links for a paginated collection
     *
     * @return bool|ApiProblem
     */
    public function injectPaginationLinks(Collection $halCollection);
}
