<?php

declare(strict_types=1);

namespace App\Service\Search\Model;

use App\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use App\Domain\Search\Query\SearchParameters;

readonly class AggregationBucketEntry
{
    /**
     * @param array<array-key,self> $subEntries
     */
    public function __construct(
        private string $key,
        private int $count,
        private FacetDisplayValueInterface $displayValue,
        private SearchParameters $parameters,
        private SearchParameters $parametersWithout,
        private array $subEntries = [],
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getDisplayValue(): FacetDisplayValueInterface
    {
        return $this->displayValue;
    }

    /**
     * @return array<array-key,self>
     */
    public function getSubEntries(): array
    {
        return $this->subEntries;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->parameters->getQueryParameters()->all();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getQueryParamsWithout(): array
    {
        return $this->parametersWithout->getQueryParameters()->all();
    }
}
