<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Service\Search\Query\Definition;

use Elastic\Elasticsearch\Response\Elasticsearch;
use Mockery;
use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Query\SearchParameters;
use Shared\Domain\Search\Query\SearchParametersFactory;
use Shared\Service\Elastic\ElasticClientInterface;
use Shared\Service\Search\Object\ObjectHandler;
use Shared\Service\Search\Query\Definition\QueryDefinitionInterface;
use Shared\Service\Search\Result\Result;
use Shared\Service\Search\Result\ResultTransformer;
use Shared\Service\Search\SearchService;
use Spatie\Snapshots\MatchesSnapshots;

trait QueryDefinitionTestTrait
{
    use MatchesSnapshots;

    /**
     * @param class-string<QueryDefinitionInterface> $definitionClass
     */
    private function matchDefinitionToSnapshot(string $definitionClass, ?SearchParameters $searchParameters = null): void
    {
        $elasticResponse = Mockery::mock(Elasticsearch::class);
        $resultTransformer = Mockery::mock(ResultTransformer::class);
        $resultTransformer->expects('transform')->andReturn(Mockery::mock(Result::class));

        $searchData = [];
        $elasticClient = Mockery::mock(ElasticClientInterface::class);
        $elasticClient->expects('search')->with(Mockery::capture($searchData))->andReturn($elasticResponse);

        $searchService = new SearchService(
            $elasticClient,
            self::getContainer()->get(LoggerInterface::class),
            self::getContainer()->get(ObjectHandler::class),
            $resultTransformer,
            self::getContainer()->get(SearchParametersFactory::class),
        );

        $queryDefinition = self::getContainer()->get($definitionClass);
        self::assertInstanceOf(QueryDefinitionInterface::class, $queryDefinition);

        $searchService->getResult(
            $queryDefinition,
            $searchParameters,
        );

        $this->assertMatchesJsonSnapshot($searchData);
    }
}
