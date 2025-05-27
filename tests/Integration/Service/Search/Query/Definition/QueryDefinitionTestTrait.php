<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Search\Query\Definition;

use App\Domain\Search\Query\SearchParameters;
use App\Domain\Search\Query\SearchParametersFactory;
use App\Service\Elastic\ElasticClientInterface;
use App\Service\Search\Object\ObjectHandler;
use App\Service\Search\Query\Definition\QueryDefinitionInterface;
use App\Service\Search\Result\Result;
use App\Service\Search\Result\ResultTransformer;
use App\Service\Search\SearchService;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Psr\Log\LoggerInterface;
use Spatie\Snapshots\MatchesSnapshots;

trait QueryDefinitionTestTrait
{
    use MatchesSnapshots;

    /**
     * @param class-string<QueryDefinitionInterface> $definitionClass
     */
    private function matchDefinitionToSnapshot(string $definitionClass, ?SearchParameters $searchParameters = null): void
    {
        $elasticResponse = \Mockery::mock(Elasticsearch::class);
        $resultTransformer = \Mockery::mock(ResultTransformer::class);
        $resultTransformer->expects('transform')->andReturn(\Mockery::mock(Result::class));

        $searchData = [];
        $elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $elasticClient->expects('search')->with(\Mockery::capture($searchData))->andReturn($elasticResponse);

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
