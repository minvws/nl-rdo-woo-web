<?php

declare(strict_types=1);

namespace App\Domain\Search\Query;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Condition\QueryConditions;
use App\Service\Search\Query\Facet\Input\DateFacetInput;
use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use Symfony\Component\HttpFoundation\ParameterBag;

final readonly class SearchParameters
{
    /**
     * @param list<string> $documentInquiries
     * @param list<string> $dossierInquiries
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        public array $documentInquiries = [],
        public array $dossierInquiries = [],
        public SortField $sortField = SortField::SCORE,
        public SortOrder $sortOrder = SortOrder::DESC,
        public ?QueryConditions $baseQueryConditions = null,
    ) {
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

    public function withFacetInput(FacetKey $key, FacetInput $facetInput): self
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
            documentInquiries: $this->documentInquiries,
            dossierInquiries: $this->dossierInquiries,
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
            documentInquiries: $this->documentInquiries,
            dossierInquiries: $this->dossierInquiries,
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

        if (! empty($this->documentInquiries)) {
            $params->set('dci', $this->documentInquiries);
        }

        if (! empty($this->documentInquiries)) {
            $params->set('dsi', $this->dossierInquiries);
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
            documentInquiries: $this->documentInquiries,
            dossierInquiries: $this->dossierInquiries,
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
            documentInquiries: $this->documentInquiries,
            dossierInquiries: $this->dossierInquiries,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
        );
    }

    public function withBaseQueryConditions(QueryConditions $queryConditions): self
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
            documentInquiries: $this->documentInquiries,
            dossierInquiries: $this->dossierInquiries,
            sortField: $this->sortField,
            sortOrder: $this->sortOrder,
            baseQueryConditions: $queryConditions,
        );
    }
}
