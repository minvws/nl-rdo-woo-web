<?php

declare(strict_types=1);

namespace App\Domain\Search\Query\Facet;

use App\Domain\Search\Query\Facet\Input\FacetInputInterface;
use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\FacetKey;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

final readonly class Facet
{
    public function __construct(
        private FacetDefinitionInterface $definition,
        public FacetInputInterface $input,
    ) {
    }

    public function getFacetKey(): FacetKey
    {
        return $this->definition->getKey();
    }

    public function getPath(): string
    {
        return $this->definition->getField()->value;
    }

    public function isActive(): bool
    {
        return $this->input->isActive();
    }

    public function isNotActive(): bool
    {
        return $this->input->isNotActive();
    }

    public function optionallyAddQueryToFilter(Facet $facet, BoolQuery $query, SearchParameters $searchParameters): void
    {
        $this->definition->getFilter()?->addToQuery($facet, $query, $searchParameters);
    }

    public function shouldExcludeOwnFilter(): bool
    {
        return $this->definition->getAggregationStrategy()?->excludeOwnFilters() === true;
    }

    public function getOptionalAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): ?AbstractAggregation
    {
        return $this->definition->getAggregationStrategy()?->getAggregation($facet, $searchParameters, $maxCount);
    }
}
