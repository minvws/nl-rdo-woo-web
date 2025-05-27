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
        foreach ($facetInputs as $facetInput) {
            $facets[] = new Facet(
                definition: $this->facetDefinitions->get($facetInput->getFacetKey()),
                input: $facetInput,
            );
        }

        return new FacetList($facets);
    }
}
