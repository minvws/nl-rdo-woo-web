<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet;

use App\Domain\Search\Query\Facet\Input\FacetInputCollection;

final readonly class FacetListFactory
{
    public function __construct(
        private FacetDefinitions $facetDefinitions,
    ) {
    }

    public function fromFacetInputs(FacetInputCollection $facetInputs): FacetList
    {
        $facets = [];
        foreach ($this->facetDefinitions as $definition) {
            $input = $facetInputs->getByFacetDefinition($definition);

            $facets[] = new Facet(
                definition: $definition,
                input: $input,
            );
        }

        return new FacetList($facets);
    }
}
