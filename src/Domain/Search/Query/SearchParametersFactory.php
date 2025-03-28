<?php

declare(strict_types=1);

namespace App\Domain\Search\Query;

use App\Domain\Search\Index\Dossier\Mapper\DepartmentFieldMapper;
use App\Domain\Search\Query\Facet\Input\FacetInputFactory;
use App\Entity\Department;
use App\Service\Search\Model\FacetKey;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

readonly class SearchParametersFactory
{
    private const DEFAULT_PAGE_SIZE = 10;
    private const MIN_PAGE_SIZE = 1;
    private const MAX_PAGE_SIZE = 100;

    public function __construct(
        private FacetInputFactory $facetInputFactory,
    ) {
    }

    public function createDefault(): SearchParameters
    {
        return new SearchParameters(
            $this->facetInputFactory->create(),
        );
    }

    /**
     * This method will convert the HTTP request to a configuration object that can be used for search. It basically
     * translates the given facets and search terms.
     */
    public function createFromRequest(
        Request $request,
        bool $pagination = true,
        bool $aggregations = true,
    ): SearchParameters {
        $pageSize = $this->getPageSize($request);
        $pageNum = max($request->query->getInt('page', 1) - 1, 0);

        $query = $request->query->getString('q', '');

        return new SearchParameters(
            facetInputs: $this->facetInputFactory->fromParameterBag($request->query),
            limit: $pageSize,
            offset: $pageNum * $pageSize,
            pagination: $pagination,
            aggregations: $aggregations,
            query: $query,
            searchType: SearchType::fromParameterBag($request->query),
            sortField: SortField::fromValue($request->query->getString('sort')),
            sortOrder: SortOrder::fromValue($request->query->getString('sortorder'))
        );
    }

    private function getPageSize(Request $request): int
    {
        $pageSize = $request->query->getInt('size', self::DEFAULT_PAGE_SIZE);

        return max(min($pageSize, self::MAX_PAGE_SIZE), self::MIN_PAGE_SIZE);
    }

    public function createForDepartment(Department $department): SearchParameters
    {
        $facetKey = FacetKey::DEPARTMENT;

        $params = new ParameterBag([
            $facetKey->getParamName() => [
                DepartmentFieldMapper::fromDepartment($department)->getIndexValue(),
            ],
        ]);

        $facetInputs = $this->facetInputFactory->create();
        $facetInputs = $facetInputs->withFacetInput(
            $facetKey,
            $this->facetInputFactory->createFacetInput($facetKey, $params)
        );

        return new SearchParameters($facetInputs);
    }
}
