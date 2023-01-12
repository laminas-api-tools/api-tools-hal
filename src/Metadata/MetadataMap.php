<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Metadata;

use Laminas\ApiTools\Hal\Exception;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\Hydrator\HydratorPluginManagerInterface;
use Laminas\ServiceManager\ServiceManager;

use function array_key_exists;
use function get_debug_type;
use function get_parent_class;
use function is_array;
use function is_object;
use function sprintf;

class MetadataMap
{
    /** @var null|HydratorPluginManager|HydratorPluginManagerInterface */
    protected $hydrators;

    /** @var array<class-string, array<string,string>|Metadata> */
    protected $map = [];

    /**
     * Constructor
     *
     * If provided, will pass $map to setMap().
     * If provided, will pass $hydrators to setHydratorManager().
     *
     * @param  null|array<class-string, array<string,string>|Metadata> $map
     * @param  null|HydratorPluginManager|HydratorPluginManagerInterface $hydrators
     */
    public function __construct(?array $map = null, $hydrators = null)
    {
        if (null !== $hydrators) {
            $this->setHydratorManager($hydrators);
        }

        if (! empty($map)) {
            $this->setMap($map);
        }
    }

    /**
     * @param  HydratorPluginManager|HydratorPluginManagerInterface $hydrators
     * @return self
     */
    public function setHydratorManager($hydrators)
    {
        if ($hydrators instanceof HydratorPluginManagerInterface) {
            $this->hydrators = $hydrators;
        } elseif ($hydrators instanceof HydratorPluginManager) {
            $this->hydrators = $hydrators;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '$hydrators argument to %s must be an instance of either %s or %s; received %s',
                self::class,
                HydratorPluginManagerInterface::class,
                HydratorPluginManager::class,
                get_debug_type($hydrators)
            ));
        }

        return $this;
    }

    /**
     * @return HydratorPluginManager|HydratorPluginManagerInterface
     */
    public function getHydratorManager()
    {
        if (null === $this->hydrators) {
            $hydrators = new HydratorPluginManager(new ServiceManager());
            $this->setHydratorManager($hydrators);
            return $hydrators;
        }

        return $this->hydrators;
    }

    /**
     * Set the metadata map
     *
     * Accepts an array of class => metadata definitions.
     * Each definition may be an instance of Metadata, or an array
     * of options used to define a Metadata instance.
     *
     * @param  array<class-string, array<string,string>|Metadata> $map
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setMap(array $map)
    {
        foreach ($map as $class => $options) {
            $metadata = $options;
            if (! is_array($metadata) && ! $metadata instanceof Metadata) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s expects each map to be an array or a Laminas\ApiTools\Hal\Metadata instance; received "%s"',
                    __METHOD__,
                    get_debug_type($metadata)
                ));
            }

            $this->map[$class] = $metadata;
        }

        return $this;
    }

    /**
     * Does the map contain metadata for the given class?
     *
     * @psalm-param  object|class-string $class Object or class name to test
     * @return bool
     */
    public function has($class)
    {
        if (is_object($class)) {
            $className = $class::class;
        } else {
            $className = $class;
        }

        if (array_key_exists($className, $this->map)) {
            return true;
        }

        if (get_parent_class($className)) {
            return $this->has(get_parent_class($className));
        }

        return false;
    }

    /**
     * Retrieve the metadata for a given class
     *
     * Lazy-loads the Metadata instance if one is not present for a matching class.
     *
     * @psalm-param object|class-string $class Object or classname for which to retrieve metadata
     * @return Metadata|false
     */
    public function get($class)
    {
        if (is_object($class)) {
            $className = $class::class;
        } else {
            $className = $class;
        }

        if (isset($this->map[$className])) {
            return $this->getMetadataInstance($className);
        }

        if (get_parent_class($className)) {
            return $this->get(get_parent_class($className));
        }

        return false;
    }

    /**
     * Retrieve a metadata instance.
     *
     * @psalm-param class-string $class
     * @return Metadata
     */
    private function getMetadataInstance($class)
    {
        if ($this->map[$class] instanceof Metadata) {
            return $this->map[$class];
        }

        $options           = $this->map[$class];
        $this->map[$class] = new Metadata($class, $options, $this->getHydratorManager());
        return $this->map[$class];
    }
}
