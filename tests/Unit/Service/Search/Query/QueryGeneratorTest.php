<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search\Query;

use App\Domain\Search\Index\ElasticDocumentType;
use App\ElasticConfig;
use App\Entity\Judgement;
use App\Service\Search\Model\Config;
use App\Service\Search\Query\AggregationGenerator;
use App\Service\Search\Query\Condition\ContentAccessConditions;
use App\Service\Search\Query\Condition\FacetConditions;
use App\Service\Search\Query\Condition\SearchTermConditions;
use App\Service\Search\Query\Facet\FacetListFactory;
use App\Service\Search\Query\Facet\Input\FacetInputFactory;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Query\SortField;
use App\Service\Search\Query\SortOrder;
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
            new Config(
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
            new Config(
                facetInputs: $this->facetInputFactory->create(),
                pagination: false,
                aggregations: false,
                searchType: Config::TYPE_DOSSIER,
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }

    public function testCreateQueryWithComplexConfig(): void
    {
        $result = $this->queryGenerator->createQuery(
            new Config(
                facetInputs: $this->facetInputFactory->fromParameterBag(
                    new ParameterBag([
                        'doctype' => [ElasticDocumentType::WOO_DECISION->value],
                        'jdg' => [Judgement::PUBLIC->value, Judgement::PARTIAL_PUBLIC->value],
                    ])
                ),
                limit: 15,
                offset: 6,
                query: 'search terms',
                documentInquiries: ['doc-inq-1'],
                dossierInquiries: ['dos-inq-1', 'dos-inq-2'],
                sortField: SortField::DECISION_DATE,
                sortOrder: SortOrder::ASC,
            )
        );

        $result = $result->build();
        self::assertMatchesJsonSnapshot($result);
        self::assertSame($this->index, $result['index']);
    }
}
