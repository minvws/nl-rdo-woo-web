<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query;

use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Query\Facet\Definition\DateFacet;
use App\Domain\Search\Query\Facet\Definition\DepartmentFacet;
use App\Domain\Search\Query\Facet\Definition\GroundsFacet;
use App\Domain\Search\Query\Facet\Definition\InquiryDocumentsFacet;
use App\Domain\Search\Query\Facet\Definition\InquiryDossiersFacet;
use App\Domain\Search\Query\Facet\Definition\JudgementFacet;
use App\Domain\Search\Query\Facet\Definition\PeriodFacet;
use App\Domain\Search\Query\Facet\Definition\PrefixedDossierNrFacet;
use App\Domain\Search\Query\Facet\Definition\SourceFacet;
use App\Domain\Search\Query\Facet\Definition\SubjectFacet;
use App\Domain\Search\Query\Facet\Definition\TypeFacet;
use App\Domain\Search\Query\Facet\FacetDefinitions;
use App\Domain\Search\Query\Facet\FacetListFactory;
use App\Domain\Search\Query\Facet\Input\FacetInputFactory;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchType;
use App\ElasticConfig;
use App\Service\Inquiry\InquirySessionService;
use App\Service\Search\Query\AggregationGenerator;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\QueryConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Query\Sort\SortField;
use App\Service\Search\Query\Sort\SortOrder;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class QueryGeneratorTest extends UnitTestCase
{
    private string $index = ElasticConfig::READ_INDEX;
    private QueryGenerator $queryGenerator;
    private FacetInputFactory $facetInputFactory;
    private InquirySessionService&MockInterface $inquirySession;

    protected function setUp(): void
    {
        parent::setUp();

        $inquiryRepo = \Mockery::mock(InquiryRepository::class);
        $this->inquirySession = \Mockery::mock(InquirySessionService::class);

        $definitions = new FacetDefinitions([
            new TypeFacet(),
            new SubjectFacet(),
            new SourceFacet(),
            new GroundsFacet(),
            new JudgementFacet(),
            new DepartmentFacet(),
            new PeriodFacet(),
            new DateFacet(),
            new PrefixedDossierNrFacet(),
            new InquiryDossiersFacet(
                $inquiryRepo,
                $this->inquirySession,
            ),
            new InquiryDocumentsFacet(
                $inquiryRepo,
                $this->inquirySession,
            ),
        ]);

        $this->facetInputFactory = new FacetInputFactory($definitions);
        $contentAccessConditions = new ContentAccessConditions();
        $facetConditions = new FacetConditions();
        $searchTermConditions = new SearchTermConditions();
        $facetListFactory = new FacetListFactory($definitions);

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

        $this->inquirySession->shouldReceive('getInquiries')->andReturn(['doc-inq-1', 'dos-inq-1', 'dos-inq-2']);

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
                        'dci' => ['doc-inq-1'],
                        'dsi' => ['dos-inq-1', 'dos-inq-2'],
                    ])
                ),
                limit: 15,
                offset: 6,
                query: 'search terms',
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
        $this->inquirySession->shouldReceive('getInquiries')->andReturn(['foo', 'bar']);

        $result = $this->queryGenerator->createQuery(
            new SearchParameters(
                facetInputs: $this->facetInputFactory->fromParameterBag(
                    new ParameterBag([
                        'dci' => ['foo', 'bar'],
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
