<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet;

use Shared\Domain\Search\Index\Schema\ElasticField;
use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Aggregation\AggregationStrategyInterface;
use Shared\Service\Search\Query\Filter\FilterInterface;
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
