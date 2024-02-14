<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet\Input;

use App\Service\Search\Model\FacetKey;
use Symfony\Component\HttpFoundation\ParameterBag;

interface ParameterBagFactoryInterface
{
    public static function fromParameterBag(FacetKey $facetKey, ParameterBag $bag): FacetInput;
}
