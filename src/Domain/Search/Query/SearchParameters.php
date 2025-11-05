<?php

declare(strict_types=1);

namespace App\Domain\Search\Query;

use App\Domain\Search\Query\Facet\FacetDefinitionInterface;
use App\Domain\Search\Query\Facet\Input\DateFacetInput;
use App\Domain\Search\Query\Facet\Input\FacetInputCollection;
use App\Domain\Search\Query\Facet\Input\FacetInputInterface;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Condition\QueryConditionBuilderInterface;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use App\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\HttpFoundation\ParameterBag;

readonly class SearchParameters
{
    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        public FacetInputCollection $facetInputs,
        public SearchOperator $operator = SearchOperator::DEFAULT,
        public int $limit = 0,
        public int $offset = 0,
        public bool $pagination = true,
        public bool $aggregations = true,
        public string $query = '',
        public SearchType $searchType = SearchType::DEFAULT,
        public SortField $sortField = SortField::SCORE,
        public SortOrder $sortOrder = SortOrder::DESC,
        public ?QueryConditionBuilderInterface $baseQueryConditions = null,
        public ApplicationMode $mode = ApplicationMode::PUBLIC,
    ) {
    }

    public function hasQueryString(): bool
    {
        return ! empty($this->query);
    }

    public function hasActiveFacets(): bool
    {
        foreach ($this->facetInputs as $facetInput) {
            if ($facetInput->isActive()) {
                return true;
            }
        }

        return false;
    }

    public function withFacetInput(FacetKey $key, FacetInputInterface $facetInput): self
    {
        return new self(
            facetInputs: $this->facetInputs->withFacetInput($key, $facetInput),
            operator: $this->operator,
            limit: $this->limit,
            offset: $this->offset,
            pagination: $this->pagination,
            aggregations: $this->aggregations,
            query: $this->query,
            searchType: $this->searchType,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
        );
    }

    public function withoutFacetFilters(): self
    {
        return new self(
            facetInputs: new FacetInputCollection(),
            operator: $this->operator,
            limit: $this->limit,
            offset: $this->offset,
            pagination: $this->pagination,
            aggregations: $this->aggregations,
            query: $this->query,
            searchType: $this->searchType,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
        );
    }

    public function withQueryString(string $query): self
    {
        return new self(
            facetInputs: $this->facetInputs,
            operator: $this->operator,
            limit: $this->limit,
            offset: $this->offset,
            pagination: $this->pagination,
            aggregations: $this->aggregations,
            query: $query,
            searchType: $this->searchType,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
        );
    }

    public function getQueryParameters(): ParameterBag
    {
        $params = new ParameterBag();

        if ($this->searchType->isNotAll()) {
            $params->set('type', $this->searchType->value);
        }

        if (! empty($this->query)) {
            $params->set('q', $this->query);
        }

        foreach ($this->facetInputs as $facetKey => $facetInput) {
            if ($facetInput->isNotActive()) {
                continue;
            }

            $params->set(
                FacetKey::from($facetKey)->getParamName(),
                $facetInput->getRequestParameters(),
            );
        }

        $params->set('sort', $this->sortField->value);
        $params->set('sortorder', $this->sortOrder->value);

        if ($this->offset > 0) {
            $params->set('page', $this->offset);
        }

        return $params;
    }

    public function withSort(SortField $sortField, SortOrder $sortOrder): self
    {
        return new self(
            facetInputs: $this->facetInputs,
            operator: $this->operator,
            limit: $this->limit,
            offset: 0,
            pagination: $this->pagination,
            aggregations: $this->aggregations,
            query: $this->query,
            searchType: $this->searchType,
            sortField: $sortField,
            sortOrder: $sortOrder,
        );
    }

    public function includeWithoutDate(): self
    {
        /** @var DateFacetInput $dateFacet */
        $dateFacet = $this->facetInputs->getByFacetKey(FacetKey::DATE);

        return new self(
            facetInputs: $this->facetInputs->withFacetInput(
                FacetKey::DATE,
                $dateFacet->includeWithoutDate()
            ),
            operator: $this->operator,
            limit: $this->limit,
            offset: 0,
            pagination: $this->pagination,
            aggregations: $this->aggregations,
            query: $this->query,
            searchType: $this->searchType,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
        );
    }

    public function withBaseQueryConditions(QueryConditionBuilderInterface $queryConditions): self
    {
        return new self(
            facetInputs: $this->facetInputs,
            operator: $this->operator,
            limit: $this->limit,
            offset: $this->offset,
            pagination: $this->pagination,
            aggregations: $this->aggregations,
            query: $this->query,
            searchType: $this->searchType,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
            baseQueryConditions: $queryConditions,
        );
    }

    public function withoutFacetFilter(FacetDefinitionInterface $facet, string $key, string $value): self
    {
        return $this->withFacetInput(
            $facet->getKey(),
            $this->facetInputs
                ->getByFacetKey($facet->getKey())
                ->without($key, $value),
        );
    }
}
