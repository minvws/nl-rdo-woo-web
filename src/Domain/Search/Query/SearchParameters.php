<?php

declare(strict_types=1);

namespace App\Domain\Search\Query;

use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Facet\Input\FacetInput;
use App\Service\Search\Query\Facet\Input\FacetInputCollection;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;
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
    ) {
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

        return $params;
    }
}
