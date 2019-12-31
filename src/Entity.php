<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal;

class Entity implements Link\LinkCollectionAwareInterface
{
    use Link\LinkCollectionAwareTrait;

    /**
     * @var object|array
     */
    protected $entity;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @param  object|array $entity
     * @param  mixed $id
     * @throws Exception\InvalidEntityException if entity is not an object or array
     */
    public function __construct($entity, $id = null)
    {
        if (! is_object($entity) && ! is_array($entity)) {
            throw new Exception\InvalidEntityException();
        }

        $this->entity      = $entity;
        $this->id          = $id;
    }

    /**
     * Retrieve properties
     *
     * @deprecated
     * @param  string $name
     * @throws Exception\InvalidArgumentException
     * @return mixed
     */
    public function &__get($name)
    {
        trigger_error(
            sprintf(
                'Direct property access to %s::$%s is deprecated, use getters instead.',
                __CLASS__,
                $name
            ),
            E_USER_DEPRECATED
        );
        $names = [
            'entity' => 'entity',
            'id'     => 'id',
        ];
        $name = strtolower($name);
        if (! in_array($name, array_keys($names))) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid property name "%s"',
                $name
            ));
        }
        $prop = $names[$name];
        return $this->{$prop};
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * TODO: Get by reference is that really necessary?
     *
     * @return object|array
     */
    public function &getEntity()
    {
        return $this->entity;
    }
}
