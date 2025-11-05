<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Search;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Search\Query\Facet\Input\FacetInputCollection;
use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Object\ObjectHandler;
use App\Service\Search\Query\Definition\QueryDefinitionInterface;
use App\Service\Search\Result\Result;
use App\Service\Search\Result\ResultTransformer;
use App\Service\Search\SearchService;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Erichard\ElasticQueryBuilder\QueryBuilder;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;

class SearchServiceTest extends MockeryTestCase
{
    use MatchesSnapshots;

    private ElasticClientInterface&MockInterface $elasticClient;
    private LoggerInterface&MockInterface $logger;
    private ObjectHandler&MockInterface $objectHandler;
    private ResultTransformer&MockInterface $resultTransformer;
    private SearchParametersFactory&MockInterface $searchParametersFactory;
    private SearchService $searchService;

    protected function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->objectHandler = \Mockery::mock(ObjectHandler::class);
        $this->resultTransformer = \Mockery::mock(ResultTransformer::class);
        $this->searchParametersFactory = \Mockery::mock(SearchParametersFactory::class);

        $this->searchService = new SearchService(
            $this->elasticClient,
            $this->logger,
            $this->objectHandler,
            $this->resultTransformer,
            $this->searchParametersFactory,
        );
    }

    public function testGetResultUsesProvidedSearchParameters(): void
    {
        $searchParameters = new SearchParameters(
            new FacetInputCollection(),
            limit: 1,
            offset: 2,
        );

        $queryDefinition = \Mockery::mock(QueryDefinitionInterface::class);
        $queryDefinition->expects('configure')->with(
            \Mockery::type(QueryBuilder::class),
            $searchParameters,
        );

        $elasticResponse = \Mockery::mock(Elasticsearch::class);
        $this->elasticClient->expects('search')->with(\Mockery::on(
            function (array $searchData): bool {
                $this->assertMatchesJsonSnapshot($searchData);

                return true;
            }
        ))->andReturn($elasticResponse);

        $result = Result::create();
        $this->resultTransformer->expects('transform')->with(
            ['body' => [], 'index' => 'woopie-read', 'from' => 2, 'size' => 1],
            $searchParameters,
            $elasticResponse,
        )->andReturn($result);

        self::assertSame(
            $result,
            $this->searchService->getResult(
                $queryDefinition,
                $searchParameters,
            ),
        );
    }

    public function testGetResultFallsBackToDefaultParameters(): void
    {
        $searchParameters = new SearchParameters(
            new FacetInputCollection(),
            limit: 1,
            offset: 2,
        );

        $this->searchParametersFactory->expects('createDefault')->andReturn($searchParameters);

        $queryDefinition = \Mockery::mock(QueryDefinitionInterface::class);
        $queryDefinition->expects('configure')->with(
            \Mockery::type(QueryBuilder::class),
            $searchParameters,
        );

        $elasticResponse = \Mockery::mock(Elasticsearch::class);
        $this->elasticClient->expects('search')->with(\Mockery::on(
            function (array $searchData): bool {
                $this->assertMatchesJsonSnapshot($searchData);

                return true;
            }
        ))->andReturn($elasticResponse);

        $result = Result::create();
        $this->resultTransformer->expects('transform')->with(
            ['body' => [], 'index' => 'woopie-read', 'from' => 2, 'size' => 1],
            $searchParameters,
            $elasticResponse,
        )->andReturn($result);

        self::assertSame(
            $result,
            $this->searchService->getResult(
                $queryDefinition,
            ),
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
}
