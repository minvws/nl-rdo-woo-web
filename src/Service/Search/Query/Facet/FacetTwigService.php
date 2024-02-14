<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;
use Symfony\Component\HttpFoundation\ParameterBag;

final readonly class FacetTwigService implements FacetDefinitionsInterface
{
    use HasFacetDefinitions;

    public function containsFacets(ParameterBag $parameterBag): bool
    {
        foreach ($this->getDefinitions() as $defition) {
            if ($parameterBag->has($defition->getParamName())) {
                return true;
            }
        }

        return false;
    }

    public function getParamKeyByFacetName(string $facet): string
    {
        return FacetKey::from($facet)->getParamName();
    }
}
