<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

use App\Service\Search\Query\Facet\FacetDefinitionsInterface;
use App\Service\Search\Query\Facet\HasFacetDefinitions;
use Symfony\Component\HttpFoundation\ParameterBag;
use Webmozart\Assert\Assert;

final readonly class FacetInputFactory implements FacetDefinitionsInterface
{
    use HasFacetDefinitions;

    /**
     * @return array<key-of<FacetKey>,FacetInput>
     */
    public function create(): array
    {
        return $this->fromParameterBag(new ParameterBag());
    }

    /**
     * @return array<key-of<FacetKey>,FacetInput>
     */
    public function fromParameterBag(ParameterBag $parameterBag): array
    {
        $facetInputs = [];
        foreach (self::getDefinitions() as $definition) {
            /** @var class-string<FacetInputInterface&ParameterBagFactoryInterface> */
            $inputClass = $definition->getFacetKey()->getInputClass();

            Assert::subclassOf($inputClass, ParameterBagFactoryInterface::class);

            $facetInputs[$definition->getFacetKey()->name] = $inputClass::fromParameterBag($definition->getFacetKey(), $parameterBag);
        }

        return $facetInputs;
    }
}
