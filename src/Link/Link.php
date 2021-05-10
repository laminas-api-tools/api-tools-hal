<?php

namespace Laminas\ApiTools\Hal\Link;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Exception;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Uri\Exception as UriException;
use Laminas\Uri\UriFactory;
use Psr\Link\LinkInterface;
use Traversable;

use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function is_string;
use function reset;
use function sprintf;

/**
 * Object describing a link relation
 */
class Link implements LinkInterface
{
    /** @var array<string,mixed> */
    protected $attributes = [];

    /** @var string[] */
    protected $rels;

    /** @var string */
    protected $route;

    /** @var array */
    protected $routeOptions = [];

    /** @var array */
    protected $routeParams = [];

    /** @var string */
    protected $href;

    /**
     * Create a link relation
     *
     * @todo  filtering and/or validation of relation string
     * @param string|array $relation
     */
    public function __construct($relation)
    {
        if (! is_array($relation)) {
            $relation = [(string) $relation];
        }

        $this->rels = $relation;
    }

    /**
     * Factory for creating links
     *
     * @param array $spec {
     *      @var string $rel Required.
     *      @var array $props Optional.
     *      @var string $href Optional.
     *      @var string|array $route Optional.
     *      @var string $url {@deprecated since 1.5.0; use 'href' instead} Optional.
     * }
     * @return self
     * @throws Exception\InvalidArgumentException If missing a "rel" or invalid route specifications.
     */
    public static function factory(array $spec)
    {
        if (! isset($spec['rel'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires that the specification array contain a "rel" element; none found',
                __METHOD__
            ));
        }
        $link = new static($spec['rel']);

        if (
            isset($spec['props'])
            && is_array($spec['props'])
        ) {
            $link->setProps($spec['props']);
        }

        // deprecated since 1.5.0; use 'href' instead
        if (isset($spec['url'])) {
            $link->setUrl($spec['url']);
            return $link;
        }

        if (isset($spec['href'])) {
            $link->href = (string) $spec['href'];
            return $link;
        }

        if (isset($spec['route'])) {
            $routeInfo = $spec['route'];
            if (is_string($routeInfo)) {
                $link->setRoute($routeInfo);
                return $link;
            }

            if (! is_array($routeInfo)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s requires that the specification array\'s "route" element be a string or array; received "%s"',
                    __METHOD__,
                    is_object($routeInfo) ? get_class($routeInfo) : gettype($routeInfo)
                ));
            }

            if (! isset($routeInfo['name'])) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s requires that the specification array\'s "route" array contain a "name" element; none found',
                    __METHOD__
                ));
            }
            $name    = $routeInfo['name'];
            $params  = isset($routeInfo['params']) && is_array($routeInfo['params'])
                ? $routeInfo['params']
                : [];
            $options = isset($routeInfo['options']) && is_array($routeInfo['options'])
                ? $routeInfo['options']
                : [];
            $link->setRoute($name, $params, $options);
            return $link;
        }

        return $link;
    }

    /**
     * Set any additional, arbitrary properties to include in the link object
     *
     * "href" will be ignored.
     *
     * @param array $props
     * @return self
     */
    public function setProps(array $props)
    {
        if (isset($props['href'])) {
            unset($props['href']);
        }
        $this->attributes = $props;
        return $this;
    }

    /**
     * Set the route to use when generating the relation URI
     *
     * If any params or options are passed, those will be passed to route assembly.
     *
     * @param  string $route
     * @param  null|array|Traversable $params
     * @param  null|array|Traversable $options
     * @return self
     * @throws DomainException
     */
    public function setRoute($route, $params = null, $options = null)
    {
        if ($this->hasUrl()) {
            throw new DomainException(sprintf(
                '%s already has a URL set; cannot set route',
                self::class
            ));
        }

        $this->route = (string) $route;
        if ($params) {
            $this->setRouteParams($params);
        }
        if ($options) {
            $this->setRouteOptions($options);
        }
        return $this;
    }

    /**
     * Set route assembly options
     *
     * @param  array|Traversable $options
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setRouteOptions($options)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (! is_array($options)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                is_object($options) ? get_class($options) : gettype($options)
            ));
        }

        $this->routeOptions = $options;
        return $this;
    }

    /**
     * Set route assembly parameters/substitutions
     *
     * @param  array|Traversable $params
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setRouteParams($params)
    {
        if ($params instanceof Traversable) {
            $params = ArrayUtils::iteratorToArray($params);
        }

        if (! is_array($params)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                is_object($params) ? get_class($params) : gettype($params)
            ));
        }

        $this->routeParams = $params;
        return $this;
    }

    /**
     * Set an explicit URL for the link relation
     *
     * @param  string $href
     * @return self
     * @throws DomainException
     * @throws Exception\InvalidArgumentException
     */
    public function setUrl($href)
    {
        if ($this->hasRoute()) {
            throw new DomainException(sprintf(
                '%s already has a route set; cannot set URL',
                self::class
            ));
        }

        try {
            $uri = UriFactory::factory($href);
        } catch (UriException\ExceptionInterface $e) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Received invalid URL: %s',
                $e->getMessage()
            ), $e->getCode(), $e);
        }

        if (! $uri->isValid()) {
            throw new Exception\InvalidArgumentException(
                'Received invalid URL'
            );
        }

        $this->href = $href;
        return $this;
    }

    /**
     * Get additional properties to include in Link representation
     *
     * @deprecated 1.4.3 Use getAttributes() instead
     *
     * @return array
     */
    public function getProps()
    {
        return $this->getAttributes();
    }

    /**
     * Retrieve the link relation
     *
     * @deprecated 1.4.3 Use getRels() and update your code to handle an array of strings
     *
     * @return string
     */
    public function getRelation()
    {
        $rels = $this->getRels();

        return (string) reset($rels);
    }

    /**
     * Return the route to be used to generate the link URL, if any
     *
     * @return null|string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Retrieve route assembly options, if any
     *
     * @return array
     */
    public function getRouteOptions()
    {
        return $this->routeOptions;
    }

    /**
     * Retrieve route assembly parameters/substitutions, if any
     *
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * Retrieve the link URL, if set
     *
     * @deprecated 1.4.3 Use getHref() instead
     *
     * @return null|string
     */
    public function getUrl()
    {
        return $this->getHref();
    }

    /**
     * Is the link relation complete -- do we have either a URL or a route set?
     *
     * @return bool
     */
    public function isComplete()
    {
        return ! empty($this->href) || ! empty($this->route);
    }

    /**
     * Does the link have a route set?
     *
     * @return bool
     */
    public function hasRoute()
    {
        return ! empty($this->route);
    }

    /**
     * Does the link have a URL set?
     *
     * @deprecated since 1.5.0; no empty URLs will be allowed in the future.
     *
     * @return bool
     */
    public function hasUrl()
    {
        return ! empty($this->href);
    }

    /**
     * Returns the target of the link.
     *
     * The target link must be one of:
     * - An absolute URI, as defined by RFC 5988.
     * - A relative URI, as defined by RFC 5988. The base of the relative link
     *   is assumed to be known based on context by the client.
     * - A URI template as defined by RFC 6570.
     *
     * If a URI template is returned, isTemplated() MUST return True.
     *
     * @return string
     */
    public function getHref()
    {
        return (string) $this->href;
    }

    /**
     * Returns whether or not this is a templated link.
     *
     * @return bool True if this link object is templated, False otherwise.
     *     Currently, templated links are not yet supported, so this will
     *     always return false.
     */
    public function isTemplated()
    {
        return false; // api-tools-hal doesn't support this currently
    }

    /**
     * Returns the relationship type(s) of the link.
     *
     * This method returns 0 or more relationship types for a link, expressed
     * as an array of strings.
     *
     * @return string[]
     */
    public function getRels()
    {
        return $this->rels;
    }

    /**
     * Returns a list of attributes that describe the target URI.
     *
     * @return array<string,mixed>
     *    A key-value list of attributes, where the key is a string and the value
     *    is either a PHP primitive or an array of PHP strings. If no values are
     *    found an empty array MUST be returned.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
