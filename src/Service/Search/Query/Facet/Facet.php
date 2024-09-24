<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Domain\Search\Query\SearchParameters;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInputInterface;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

final readonly class Facet
{
    public function __construct(
        private FacetDefinition $definition,
        public FacetInputInterface $input,
    ) {
    }

    public function getFacetKey(): FacetKey
    {
        return $this->definition->getFacetKey();
    }

    public function getPath(): string
    {
        return $this->definition->getPath();
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
        $this->definition->optionallyAddQueryToFilter($facet, $query, $searchParameters);
    }

    public function shouldExcludeOwnFilter(): bool
    {
        return $this->definition->shouldExcludeOwnFilter();
    }

    public function getOptionalAggregation(Facet $facet, SearchParameters $searchParameters, int $maxCount): ?AbstractAggregation
    {
        return $this->definition->getOptionalAggregation($facet, $searchParameters, $maxCount);
    }
}
