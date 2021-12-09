<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal;

use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated
 */
class Resource extends Entity
{
    /**
     * @param  object|array $resource
     * @param  mixed $id
     * @throws Exception\InvalidResourceException If resource is not an object or array.
     */
    public function __construct($resource, $id)
    {
        trigger_error(sprintf(
            '%s is deprecated; please use %s\Entity instead',
            self::class,
            __NAMESPACE__
        ), E_USER_DEPRECATED);
        parent::__construct($resource, $id);
    }
}
