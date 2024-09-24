<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\FacetDefinitionsInterface;
use App\Service\Search\Query\Facet\HasFacetDefinitions;
use Symfony\Component\HttpFoundation\ParameterBag;
use Webmozart\Assert\Assert;

readonly class FacetInputFactory implements FacetDefinitionsInterface
{
    use HasFacetDefinitions;

    public function create(): FacetInputCollection
    {
        return $this->fromParameterBag(new ParameterBag());
    }

    public function fromParameterBag(ParameterBag $parameterBag): FacetInputCollection
    {
        $facetInputs = [];
        foreach ($this->getDefinitions() as $definition) {
            $facetKey = $definition->getFacetKey();

            $facetInputs[$facetKey->value] = $this->createFacetInput($facetKey, $parameterBag);
        }

        return new FacetInputCollection(...$facetInputs);
    }

    public function createFacetInput(FacetKey $facetKey, ParameterBag $parameterBag): FacetInput
    {
        /** @var class-string<FacetInputInterface> */
        $inputClass = $facetKey->getInputClass();

        Assert::subclassOf($inputClass, FacetInputInterface::class);

        return $inputClass::fromParameterBag($facetKey, $parameterBag);
    }

    public function createStringFacetInputForValue(FacetKey $facetKey, string ...$values): FacetInput
    {
        Assert::subclassOf($facetKey->getInputClass(), StringValuesFacetInputInterface::class);

        return $facetKey->getInputClass()::fromParameterBag($facetKey, new ParameterBag([
            $facetKey->getParamName() => $values,
        ]));
    }
}
