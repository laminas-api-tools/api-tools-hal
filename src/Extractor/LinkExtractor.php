<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkUrlBuilder;

use function sprintf;

class LinkExtractor implements LinkExtractorInterface
{
    /** @var LinkUrlBuilder */
    protected $linkUrlBuilder;

    public function __construct(LinkUrlBuilder $linkUrlBuilder)
    {
        $this->linkUrlBuilder = $linkUrlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function extract(Link $link)
    {
        if (! $link->isComplete()) {
            throw new DomainException(sprintf(
                'Link from resource provided to %s was incomplete; must contain a URL or a route',
                __METHOD__
            ));
        }

        $representation = $link->getAttributes();

        if ($link->hasUrl()) {
            $representation['href'] = $link->getHref();

            return $representation;
        }

        $reuseMatchedParams = true;
        $options            = $link->getRouteOptions();
        if (isset($options['reuse_matched_params'])) {
            $reuseMatchedParams = (bool) $options['reuse_matched_params'];
            unset($options['reuse_matched_params']);
        }

        $representation['href'] = $this->linkUrlBuilder->buildLinkUrl(
            $link->getRoute(),
            $link->getRouteParams(),
            $options,
            $reuseMatchedParams
        );

        return $representation;
    }
}
