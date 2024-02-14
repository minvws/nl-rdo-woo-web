<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\FacetInputInterface;
use App\Service\Search\Query\Facet\Input\ParameterBagFactoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Webmozart\Assert\Assert;

final readonly class FacetListFactory implements FacetDefinitionsInterface
{
    use HasFacetDefinitions;

    public function fromParameterBag(ParameterBag $parameterBag = new ParameterBag()): FacetList
    {
        $facets = [];
        foreach (self::getDefinitions() as $definition) {
            /** @var class-string<FacetInputInterface&ParameterBagFactoryInterface> */
            $inputClass = $definition->getFacetKey()->getInputClass();

            Assert::implementsInterface($inputClass, ParameterBagInterface::class);

            $facets[] = new Facet(
                definition: $definition,
                input: $inputClass::fromParameterBag($definition->getFacetKey(), $parameterBag)
            );
        }

        return new FacetList($facets);
    }

    /**
     * @param array<key-of<FacetKey>,FacetInput> $facetInputs
     */
    public function fromFacetInputs(array $facetInputs): FacetList
    {
        $facets = [];
        foreach (self::getDefinitions() as $definition) {
            Assert::keyExists($facetInputs, $definition->getFacetKey()->name);

            $input = $facetInputs[$definition->getFacetKey()->name];

            /** @var class-string<FacetInputInterface&ParameterBagFactoryInterface> */
            $inputClass = $definition->getFacetKey()->getInputClass();

            Assert::isInstanceOf($input, $inputClass);

            $facets[] = new Facet(
                definition: $definition,
                input: $input,
            );
        }

        return new FacetList($facets);
    }
}
