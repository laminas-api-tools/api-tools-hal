<?php

declare(strict_types=1);

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;

use function is_array;
use function sprintf;

class LinkCollectionExtractor implements LinkCollectionExtractorInterface
{
    /** @var LinkExtractorInterface */
    protected $linkExtractor;

    public function __construct(LinkExtractorInterface $linkExtractor)
    {
        $this->setLinkExtractor($linkExtractor);
    }

    /**
     * @return LinkExtractorInterface
     */
    public function getLinkExtractor()
    {
        return $this->linkExtractor;
    }

    /**
     * @return void
     */
    public function setLinkExtractor(LinkExtractorInterface $linkExtractor)
    {
        $this->linkExtractor = $linkExtractor;
    }

    /**
     * @inheritDoc
     */
    public function extract(LinkCollection $collection)
    {
        $links = [];
        foreach ($collection as $rel => $linkDefinition) {
            if ($linkDefinition instanceof Link) {
                $links[$rel] = $this->linkExtractor->extract($linkDefinition);
                continue;
            }

            if (! is_array($linkDefinition)) {
                throw new DomainException(sprintf(
                    'Link object for relation "%s" in resource was malformed; cannot generate link',
                    $rel
                ));
            }

            $aggregate = [];
            foreach ($linkDefinition as $subLink) {
                if (! $subLink instanceof Link) {
                    throw new DomainException(sprintf(
                        'Link object aggregated for relation "%s" in resource was malformed; cannot generate link',
                        $rel
                    ));
                }

                $aggregate[] = $this->linkExtractor->extract($subLink);
            }

            $links[$rel] = $aggregate;
        }

        return $links;
    }
}
