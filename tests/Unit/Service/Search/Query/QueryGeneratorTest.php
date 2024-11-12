<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query;

use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\ElasticConfig;
use App\Entity\Judgement;
use App\Service\Search\Query\AggregationGenerator;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\QueryConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Facet\FacetListFactory;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class QueryGeneratorTest extends UnitTestCase
{
    private string $index = ElasticConfig::READ_INDEX;
    private QueryGenerator $queryGenerator;
    private FacetInputFactory $facetInputFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facetInputFactory = new FacetInputFactory();
        $contentAccessConditions = new ContentAccessConditions();
        $facetConditions = new FacetConditions();
        $searchTermConditions = new SearchTermConditions();
        $facetListFactory = new FacetListFactory();

        $this->queryGenerator = new QueryGenerator(
            new AggregationGenerator(
                $contentAccessConditions,
                $facetConditions,
                $searchTermConditions
            ),
            $contentAccessConditions,
            $facetConditions,
            $searchTermConditions,
            $facetListFactory,
        );
    }

    public function testCreateQueryWithMinimalConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new SearchParameters(
                facetInputs: $this->facetInputFactory->create(),
                pagination: false,
                aggregations: false,
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }

    public function testCreateQueryWithDossierOnlyConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new SearchParameters(
                facetInputs: $this->facetInputFactory->create(),
                pagination: false,
                aggregations: false,
                searchType: SearchType::DOSSIER,
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }

    public function testCreateQueryWithComplexConfig(): void
    {
        $baseQueryConditions = \Mockery::mock(QueryConditions::class);
        $baseQueryConditions->expects('applyToQuery')->twice(); // Second time in global aggregations!

        $result = $this->queryGenerator->createQuery(
            new SearchParameters(
                facetInputs: $this->facetInputFactory->fromParameterBag(
                    new ParameterBag([
                        'doctype' => [
                            ElasticDocumentType::WOO_DECISION->value,
                            ElasticDocumentType::WOO_DECISION->value . '.publication',
                        ],
                        'jdg' => [Judgement::PUBLIC->value, Judgement::PARTIAL_PUBLIC->value],
                        'dt' => ['from' => '2020-01-12', 'to' => '2024-06-20'],
                    ])
                ),
                limit: 15,
                offset: 6,
                query: 'search terms',
                documentInquiries: ['doc-inq-1'],
                dossierInquiries: ['dos-inq-1', 'dos-inq-2'],
                sortField: SortField::DECISION_DATE,
                sortOrder: SortOrder::ASC,
                baseQueryConditions: $baseQueryConditions,
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }

    public function testCreateQueryWithInquiryDocumentsFilter(): void
    {
        $result = $this->queryGenerator->createQuery(
            new SearchParameters(
                facetInputs: $this->facetInputFactory->create(),
                pagination: false,
                aggregations: false,
                documentInquiries: ['foo', 'bar'],
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }

    public function testCreateQueryWithOutDateFilter(): void
    {
        $result = $this->queryGenerator->createQuery(
            new SearchParameters(
                facetInputs: $this->facetInputFactory->fromParameterBag(
                    new ParameterBag([
                        'dt' => ['without_date' => '1'],
                    ])
                ),
                pagination: false,
                aggregations: false,
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }
}
