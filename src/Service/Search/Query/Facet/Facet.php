<?php

declare(strict_types=1);

namespace App\Service\Search\Query\Facet;

use App\Service\Search\Model\Config;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInput;
use Erichard\ElasticQueryBuilder\Aggregation\AbstractAggregation;
use Erichard\ElasticQueryBuilder\Query\BoolQuery;

final readonly class Facet
{
    public function __construct(
        private FacetDefinition $definition,
        public FacetInput $input,
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

    public function optionallyAddQueryToFilter(Facet $facet, BoolQuery $query, Config $config): void
    {
        $this->definition->optionallyAddQueryToFilter($facet, $query, $config);
    }

    public function shouldExcludeOwnFilter(): bool
    {
        return $this->definition->shouldExcludeOwnFilter();
    }

    public function getOptionalAggregation(Facet $facet, Config $config, int $maxCount): ?AbstractAggregation
    {
        return $this->definition->getOptionalAggregation($facet, $config, $maxCount);
    }
}
