<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal;

use Closure;
use Laminas\ApiTools\Hal\Exception;
use laminas\apitools\hal\extractor\entityextractor;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;
use Laminas\ApiTools\Hal\Metadata\Metadata;
use Laminas\Paginator\Paginator;
use Traversable;

use function array_merge;
use function call_user_func_array;
use function get_class;
use function is_callable;
use function sprintf;

class ResourceFactory
{
    /** @var EntityHydratorManager */
    protected $entityHydratorManager;

    /** @var entityextractor */
    protected $entityExtractor;

    public function __construct(EntityHydratorManager $entityHydratorManager, entityextractor $entityExtractor)
    {
        $this->entityHydratorManager = $entityHydratorManager;
        $this->entityExtractor       = $entityExtractor;
    }

    /**
     * Create a entity and/or collection based on a metadata map
     *
     * @param  object|array|Traversable|Paginator $object
     * @param  bool $renderEmbeddedEntities
     * @return Entity|Collection
     * @throws Exception\RuntimeException
     */
    public function createEntityFromMetadata($object, Metadata $metadata, $renderEmbeddedEntities = true)
    {
        if ($metadata->isCollection()) {
            return $this->createCollectionFromMetadata($object, $metadata);
        }

        $data = $this->entityExtractor->extract($object);

        $entityIdentifierName = $metadata->getEntityIdentifierName();
        if ($entityIdentifierName && ! isset($data[$entityIdentifierName])) {
            throw new Exception\RuntimeException(sprintf(
                'Unable to determine entity identifier for object of type "%s"; no fields matching "%s"',
                get_class($object),
                $entityIdentifierName
            ));
        }

        $id = $entityIdentifierName ? $data[$entityIdentifierName] : null;

        if (! $renderEmbeddedEntities) {
            $object = [];
        }

        $halEntity = new Entity($object, $id);

        $links = $halEntity->getLinks();
        $this->marshalMetadataLinks($metadata, $links);

        $forceSelfLink = $metadata->getForceSelfLink();
        if ($forceSelfLink && ! $links->has('self')) {
            $link = $this->marshalLinkFromMetadata(
                $metadata,
                $object,
                $id,
                $metadata->getRouteIdentifierName()
            );
            $links->add($link);
        }

        return $halEntity;
    }

    /**
     * @param  array|Traversable|Paginator $object
     * @return Collection
     */
    public function createCollectionFromMetadata($object, Metadata $metadata)
    {
        $halCollection = new Collection($object);
        $halCollection->setCollectionName($metadata->getCollectionName());
        $halCollection->setCollectionRoute($metadata->getRoute());
        $halCollection->setEntityRoute($metadata->getEntityRoute());
        $halCollection->setRouteIdentifierName($metadata->getRouteIdentifierName());
        $halCollection->setEntityIdentifierName($metadata->getEntityIdentifierName());

        $links = $halCollection->getLinks();
        $this->marshalMetadataLinks($metadata, $links);

        $forceSelfLink = $metadata->getForceSelfLink();
        if (
            $forceSelfLink && ! $links->has('self')
            && ($metadata->hasUrl() || $metadata->hasRoute())
        ) {
            $link = $this->marshalLinkFromMetadata($metadata, $object);
            $links->add($link);
        }

        return $halCollection;
    }

    /**
     * Creates a link object, given metadata and a resource
     *
     * @param  object $object
     * @param  null|string $id
     * @param  null|string $routeIdentifierName
     * @param  string $relation
     * @return Link
     * @throws Exception\RuntimeException
     */
    public function marshalLinkFromMetadata(
        Metadata $metadata,
        $object,
        $id = null,
        $routeIdentifierName = null,
        $relation = 'self'
    ) {
        $link = new Link($relation);
        if ($metadata->hasUrl()) {
            $link->setUrl($metadata->getUrl());
            return $link;
        }

        if (! $metadata->hasRoute()) {
            throw new Exception\RuntimeException(sprintf(
                'Unable to create a self link for resource of type "%s"; metadata does not contain a route or a href',
                get_class($object)
            ));
        }

        $params = $metadata->getRouteParams();

        // process any callbacks
        foreach ($params as $key => $param) {
            // bind to the object
            if ($param instanceof Closure) {
                $param = $param->bindTo($object);
            }

            // pass the object for callbacks
            if (is_callable($param)) {
                $params[$key] = call_user_func_array($param, [$object]);
            }
        }

        if ($routeIdentifierName) {
            $params = array_merge($params, [$routeIdentifierName => $id]);
        }

        $link->setRoute($metadata->getRoute(), $params, $metadata->getRouteOptions());
        return $link;
    }

    /**
     * Inject any links found in the metadata into the resource's link collection
     *
     * @return void
     */
    public function marshalMetadataLinks(Metadata $metadata, LinkCollection $links)
    {
        foreach ($metadata->getLinks() as $linkData) {
            $link = Link::factory($linkData);
            $links->add($link);
        }
    }
}
