<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class FacetDefinitions implements \IteratorAggregate
{
    /**
     * @var array<array-key, FacetDefinitionInterface>
     */
    private array $definitions;

    /**
     * @param iterable<FacetDefinitionInterface> $definitions
     */
    public function __construct(
        #[AutowireIterator('domain.search.query.facet_definition')]
        iterable $definitions,
    ) {
        $keyedSet = [];
        foreach ($definitions as $definition) {
            $keyedSet[$definition->getKey()->value] = $definition;
        }

        $this->definitions = $keyedSet;
    }

    /**
     * @return \Traversable<FacetDefinitionInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->definitions);
    }

    public function get(FacetKey $facetKey): FacetDefinitionInterface
    {
        if (! array_key_exists($facetKey->value, $this->definitions)) {
            throw new \OutOfBoundsException(sprintf('No facet definition found with key %s', $facetKey->value));
        }

        return $this->definitions[$facetKey->value];
    }
}
