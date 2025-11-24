<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Query;

use Shared\Api\Admin\Publication\Search\SearchResultType;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Search\Index\Dossier\Mapper\DepartmentFieldMapper;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Query\Facet\Definition\PrefixedDossierNrFacet;
use Shared\Domain\Search\Query\Facet\Definition\TypeFacet;
use Shared\Domain\Search\Query\Facet\Input\FacetInputFactory;
use Shared\Domain\Search\Query\Facet\Input\StringValuesFacetInput;
use Shared\Service\Search\Model\FacetKey;
use Shared\Service\Search\Query\Sort\SortField;
use Shared\Service\Search\Query\Sort\SortOrder;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class SearchParametersFactory
{
    private const int DEFAULT_PAGE_SIZE = 10;
    private const int MIN_PAGE_SIZE = 1;
    private const int MAX_PAGE_SIZE = 100;

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

    public function forAdminSearch(
        string $searchTerm,
        ?DossierType $dossierType,
        ?string $dossierNr,
        ?SearchResultType $resultType,
    ): SearchParameters {
        $facetInputs = $this->facetInputFactory->createEmpty();

        if ($dossierNr !== null) {
            $facetInputs = $facetInputs->withFacetInput(
                FacetKey::PREFIXED_DOSSIER_NR,
                new StringValuesFacetInput(
                    new PrefixedDossierNrFacet(),
                    [$dossierNr],
                )
            );
        }

        if ($dossierType !== null) {
            $facetInputs = $facetInputs->withFacetInput(
                FacetKey::TYPE,
                new StringValuesFacetInput(
                    new TypeFacet(),
                    [ElasticDocumentType::fromDossierType($dossierType)->value],
                )
            );
        }

        if ($resultType !== null) {
            if ($resultType === SearchResultType::DOSSIER && $dossierType !== null) {
                $dossierTypes = [ElasticDocumentType::fromDossierType($dossierType)->value];
            } else {
                $dossierTypes = ElasticDocumentType::getMainTypeValues();
            }

            $types = match ($resultType) {
                SearchResultType::DOSSIER => array_map(
                    static fn (string $type): string => $type . '.publication',
                    $dossierTypes,
                ),
                SearchResultType::DOCUMENT => [sprintf(
                    '%s.%s',
                    ElasticDocumentType::WOO_DECISION->value,
                    ElasticDocumentType::WOO_DECISION_DOCUMENT->value,
                )],
                SearchResultType::MAIN_DOCUMENT => ElasticDocumentType::getMainDocumentTypeValues(),
                SearchResultType::ATTACHMENT => [ElasticDocumentType::ATTACHMENT->value],
            };

            $facetInputs = $facetInputs->withFacetInput(
                FacetKey::TYPE,
                new StringValuesFacetInput(new TypeFacet(), $types),
            );
        }

        return new SearchParameters(
            facetInputs: $facetInputs,
            limit: 15,
            pagination: false,
            aggregations: false,
            query: $searchTerm,
            mode: ApplicationMode::ADMIN,
        );
    }
}
