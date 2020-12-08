<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-hal for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-hal/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\Hal\Extractor;

use Laminas\ApiTools\ApiProblem\Exception\DomainException;
use Laminas\ApiTools\Hal\Link\Link;
use Laminas\ApiTools\Hal\Link\LinkCollection;

class LinkCollectionExtractor implements LinkCollectionExtractorInterface
{
    /**
     * @var LinkExtractorInterface
     */
    protected $linkExtractor;

    public function __construct(LinkExtractorInterface $linkExtractor)
    {
        $this->setLinkExtractor($linkExtractor);
    }

    public function getLinkExtractor(): LinkExtractorInterface
    {
        return $this->linkExtractor;
    }

    public function setLinkExtractor(LinkExtractorInterface $linkExtractor): void
    {
        $this->linkExtractor = $linkExtractor;
    }

    /**
     * @inheritDoc
     */
    public function extract(LinkCollection $collection): array
    {
        $links = [];
        foreach ($collection as $rel => $linkDefinition) {
            if ($linkDefinition instanceof Link) {
                $links[$rel] = $this->linkExtractor->extract($linkDefinition);
                continue;
            }

            if (! \is_array($linkDefinition)) {
                throw new DomainException(\sprintf(
                    'Link object for relation "%s" in resource was malformed; cannot generate link',
                    $rel
                ));
            }

            $aggregate = [];
            foreach ($linkDefinition as $subLink) {
                if (! $subLink instanceof Link) {
                    throw new DomainException(\sprintf(
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
