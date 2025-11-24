<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query\Facet;

use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;
use Shared\Domain\Search\Query\Facet\Input\FacetInputInterface;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Service\Search\Model\FacetKey;

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
