<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet;

use App\Domain\Search\Index\Schema\ElasticField;
use App\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use App\Domain\Search\Query\Facet\Input\FacetInputInterface;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use App\Service\Search\Query\Filter\FilterInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\ParameterBag;

#[AutoconfigureTag('domain.search.query.facet_definition')]
interface FacetDefinitionInterface
{
    public function getKey(): FacetKey;

    public function getField(): ElasticField;

    public function getRequestParameter(): string;

    public function getQueryParameter(string $key): string;

    public function getFilter(): ?FilterInterface;

    public function getAggregationStrategy(): ?AggregationStrategyInterface;

    public function getInput(ParameterBag $parameters): FacetInputInterface;

    public function displayActiveSelection(int|string $key, string $value): bool;

    public function getTitle(int|string $key, string $value): FacetDisplayValueInterface;

    public function getDisplayValue(int|string $key, string $value): FacetDisplayValueInterface;

    public function getDescription(int|string $key, string $value): ?FacetDisplayValueInterface;
}
