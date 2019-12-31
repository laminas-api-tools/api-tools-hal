<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;

class LinkExtractor implements LinkExtractorInterface
{
    /**
     * @var LinkUrlBuilder
     */
    protected $linkUrlBuilder;

    /**
     * @param  LinkUrlBuilder $linkUrlBuilder
     */
    public function __construct(LinkUrlBuilder $linkUrlBuilder)
    {
        $this->linkUrlBuilder = $linkUrlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function extract(Link $object)
    {
        if (! $object->isComplete()) {
            throw new DomainException(sprintf(
                'Link from resource provided to %s was incomplete; must contain a URL or a route',
                __METHOD__
            ));
        }

        $representation = $object->getProps();

        if ($object->hasUrl()) {
            $representation['href'] = $object->getUrl();

            return $representation;
        }

        $reuseMatchedParams = true;
        $options = $object->getRouteOptions();
        if (isset($options['reuse_matched_params'])) {
            $reuseMatchedParams = (bool) $options['reuse_matched_params'];
            unset($options['reuse_matched_params']);
        }

        $representation['href'] = $this->linkUrlBuilder->buildLinkUrl(
            $object->getRoute(),
            $object->getRouteParams(),
            $options,
            $reuseMatchedParams
        );

        return $representation;
    }
}
