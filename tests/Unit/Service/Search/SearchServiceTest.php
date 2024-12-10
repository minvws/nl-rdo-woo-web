<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Object\ObjectHandler;
use App\Service\Search\Query\QueryGenerator;
use App\Service\Search\Result\Result;
use App\Service\Search\Result\ResultTransformer;
use App\Service\Search\SearchService;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

class SearchServiceTest extends MockeryTestCase
{
    private ElasticClientInterface&MockInterface $elasticClient;
    private LoggerInterface&MockInterface $logger;
    private QueryGenerator&MockInterface $queryGenerator;
    private ObjectHandler&MockInterface $objectHandler;
    private ResultTransformer&MockInterface $resultTransformer;
    private SearchParametersFactory&MockInterface $searchParametersFactory;
    private SearchService $searchService;

    public function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->queryGenerator = \Mockery::mock(QueryGenerator::class);
        $this->objectHandler = \Mockery::mock(ObjectHandler::class);
        $this->resultTransformer = \Mockery::mock(ResultTransformer::class);
        $this->searchParametersFactory = \Mockery::mock(SearchParametersFactory::class);

        $this->searchService = new SearchService(
            $this->elasticClient,
            $this->logger,
            $this->queryGenerator,
            $this->objectHandler,
            $this->resultTransformer,
            $this->searchParametersFactory,
        );
    }

    public function testSearchFacets(): void
    {
        $facetsQuery = \Mockery::mock(QueryBuilder::class);
        $facetsQuery->expects('build')->andReturn($queryArray = ['foo' => 'bar']);

        $params = \Mockery::mock(SearchParameters::class);
        $this->queryGenerator->expects('createFacetsQuery')->with($params)->andReturn($facetsQuery);

        $elasticResponse = \Mockery::mock(Elasticsearch::class);
        $this->elasticClient->expects('search')->with($queryArray)->andReturn($elasticResponse);

        $result = Result::create();
        $this->resultTransformer->expects('transform')->with($queryArray, $params, $elasticResponse)->andReturn($result);

        self::assertEquals(
            $result,
            $this->searchService->searchFacets($params),
        );
    }

    public function testSearch(): void
    {
        $facetsQuery = \Mockery::mock(QueryBuilder::class);
        $facetsQuery->expects('build')->andReturn($queryArray = ['foo' => 'bar']);

        $params = \Mockery::mock(SearchParameters::class);
        $this->queryGenerator->expects('createQuery')->with($params)->andReturn($facetsQuery);

        $elasticResponse = \Mockery::mock(Elasticsearch::class);
        $this->elasticClient->expects('search')->with($queryArray)->andReturn($elasticResponse);

        $result = Result::create();
        $this->resultTransformer->expects('transform')->with($queryArray, $params, $elasticResponse)->andReturn($result);

        self::assertEquals(
            $result,
            $this->searchService->search($params),
        );
    }

    public function testIsIngested(): void
    {
        $document = \Mockery::mock(Document::class);
        $this->objectHandler->expects('isIngested')->with($document)->andReturnFalse();

        self::assertFalse($this->searchService->isIngested($document));
    }

    public function testGetPageContent(): void
    {
        $document = \Mockery::mock(Document::class);
        $this->objectHandler->expects('getPageContent')->with($document, 123)->andReturn($expectedResult = 'foo bar');

        self::assertEquals(
            $expectedResult,
            $this->searchService->getPageContent($document, 123),
        );
    }

    public function testRetrieveExtendedFacets(): void
    {
        $facetsQuery = \Mockery::mock(QueryBuilder::class);
        $facetsQuery->expects('build')->andReturn($queryArray = ['foo' => 'bar']);

        $params = \Mockery::mock(SearchParameters::class);
        $this->queryGenerator->expects('createExtendedFacetsQuery')->with($params)->andReturn($facetsQuery);

        $this->searchParametersFactory->expects('createDefault')->andReturn($params);

        $elasticResponse = \Mockery::mock(Elasticsearch::class);
        $this->elasticClient->expects('search')->with($queryArray)->andReturn($elasticResponse);

        $result = Result::create();
        $this->resultTransformer->expects('transform')->with($queryArray, $params, $elasticResponse)->andReturn($result);

        self::assertEquals(
            $result,
            $this->searchService->retrieveExtendedFacets(),
        );
    }
}
