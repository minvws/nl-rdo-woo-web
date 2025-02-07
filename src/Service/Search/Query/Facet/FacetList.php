<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;

/**
 * @implements \IteratorAggregate<array-key,Facet>
 */
final readonly class FacetList implements \IteratorAggregate
{
    /**
     * @param array<array-key,Facet> $facets
     */
    public function __construct(
        private readonly array $facets,
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

    public function getFacetByKey(FacetKey $key): Facet
    {
        foreach ($this->facets as $facet) {
            if ($facet->getFacetKey() === $key) {
                return $facet;
            }
        }

        throw new \RuntimeException('Cannot find facet mapping by key ' . $key->value);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->facets);
    }
}
