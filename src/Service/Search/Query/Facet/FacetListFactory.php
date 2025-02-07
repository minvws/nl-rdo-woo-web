<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\Facet\Input\FacetInputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

final readonly class FacetListFactory implements FacetDefinitionsInterface
{
    use HasFacetDefinitions;

    public function fromParameterBag(ParameterBag $parameterBag = new ParameterBag()): FacetList
    {
        $facets = [];
        foreach ($this->getDefinitions() as $definition) {
            /** @var class-string<FacetInputInterface> */
            $inputClass = $definition->getFacetKey()->getInputClass();

            $facets[] = new Facet(
                definition: $definition,
                input: $inputClass::fromParameterBag($definition->getFacetKey(), $parameterBag)
            );
        }

        return new FacetList($facets);
    }

    public function fromFacetInputs(FacetInputCollection $facetInputs): FacetList
    {
        $facets = [];
        foreach ($this->getDefinitions() as $definition) {
            $input = $facetInputs->getByFacetDefinition($definition);

            $facets[] = new Facet(
                definition: $definition,
                input: $input,
            );
        }

        return new FacetList($facets);
    }
}
