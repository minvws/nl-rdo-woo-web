<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use Symfony\Component\HttpFoundation\ParameterBag;

interface FacetInputInterface
{
    public function isActive(): bool;

    public function isNotActive(): bool;

    public static function fromParameterBag(FacetKey $facetKey, ParameterBag $bag): FacetInput;

    /**
     * @return array<array-key, mixed>
     */
    public function getRequestParameters(): array;
}
