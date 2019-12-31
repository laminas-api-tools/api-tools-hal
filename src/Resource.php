<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

/**
 * @deprecated
 */
class Resource extends Entity
{
    /**
     * @param  object|array $resource
     * @param  mixed $id
     * @throws Exception\InvalidResourceException if resource is not an object or array
     */
    public function __construct($resource, $id)
    {
        trigger_error(sprintf(
            '%s is deprecated; please use %s\Entity instead',
            __CLASS__,
            __NAMESPACE__
        ), E_USER_DEPRECATED);
        parent::__construct($resource, $id);
    }
}
