<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

use function array_filter;

/**
 * @implements IteratorAggregate<array-key,Facet>
 */
final readonly class FacetList implements IteratorAggregate
{
    /**
     * @param array<array-key,Facet> $facets
     */
    public function __construct(
        private array $facets,
    ) {
    }

    /**
     * @return array<array-key,Facet>
     */
    public function getActiveFacets(): array
    {
        return array_filter(
            $this->facets,
            static fn (Facet $facet): bool => $facet->input->isActive(),
        );
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->facets);
    }
}
