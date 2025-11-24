<?php

declare(strict_types=1);

namespace Shared\Service\Search\Model;

use Shared\Domain\Search\Query\Facet\DisplayValue\FacetDisplayValueInterface;
use Shared\Domain\Search\Query\SearchParameters;

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
        private ?int $countWithoutSubEntries = null,
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

    public function getCountWithoutSubEntries(): int
    {
        return $this->countWithoutSubEntries ?? $this->count;
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
