<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\FacetKey;

final readonly class FacetTwigService implements FacetDefinitionsInterface
{
    use HasFacetDefinitions;

    public function getParamKeyByFacetName(string $facet): string
    {
        return FacetKey::from($facet)->getParamName();
    }
}
