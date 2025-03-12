<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet\Input;

use App\Domain\Search\Query\Facet\FacetDefinitionInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

interface FacetInputInterface
{
    public function isActive(): bool;

    public function isNotActive(): bool;

    public static function fromParameterBag(FacetDefinitionInterface $facet, ParameterBag $bag): FacetInput;

    /**
     * @return array<array-key, mixed>
     */
    public function getRequestParameters(): array;

    public function without(int|string $key, string $value): self;
}
