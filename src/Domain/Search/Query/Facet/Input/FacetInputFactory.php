<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Input;

use App\Domain\Search\Query\Facet\FacetDefinitions;
use App\Service\Search\Model\FacetKey;
use Symfony\Component\HttpFoundation\ParameterBag;

readonly class FacetInputFactory
{
    public function __construct(
        private FacetDefinitions $facetDefinitions,
    ) {
    }

    public function create(): FacetInputCollection
    {
        return $this->fromParameterBag(new ParameterBag());
    }

    public function fromParameterBag(ParameterBag $parameterBag): FacetInputCollection
    {
        $facetInputs = [];
        foreach ($this->facetDefinitions as $definition) {
            $facetInputs[$definition->getKey()->value] = $definition->getInput($parameterBag);
        }

        return new FacetInputCollection(...$facetInputs);
    }

    public function createFacetInput(FacetKey $facetKey, ParameterBag $parameterBag): FacetInputInterface
    {
        return $this->facetDefinitions->get($facetKey)->getInput($parameterBag);
    }

    public function createStringFacetInputForValue(FacetKey $facetKey, string ...$values): FacetInputInterface
    {
        $facet = $this->facetDefinitions->get($facetKey);

        return $facet->getInput(
            new ParameterBag([
                $facet->getRequestParameter() => $values,
            ])
        );
    }
}
