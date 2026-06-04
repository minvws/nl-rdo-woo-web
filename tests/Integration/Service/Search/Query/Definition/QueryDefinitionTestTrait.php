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
use Shared\Tests\ElasticConfigFactory;
use Spatie\Snapshots\MatchesSnapshots;

trait QueryDefinitionTestTrait
{
    use MatchesSnapshots;

    /**
     * @param class-string<QueryDefinitionInterface> $definitionClass
     *
     * @return array<string, mixed>
     */
    private function getDefinitionSearchData(string $definitionClass, ?SearchParameters $searchParameters = null): array
    {
        $elasticResponse = Mockery::mock(Elasticsearch::class);
        $resultTransformer = Mockery::mock(ResultTransformer::class);
        $resultTransformer->expects('transform')->andReturn(Mockery::mock(Result::class));

        $searchData = [];
        $elasticClient = Mockery::mock(ElasticClientInterface::class);
        $elasticClient->expects('search')->with(Mockery::capture($searchData))->andReturn($elasticResponse);

        $searchService = new SearchService(
            $elasticClient,
            self::fromContainer(LoggerInterface::class),
            self::fromContainer(ObjectHandler::class),
            $resultTransformer,
            self::fromContainer(SearchParametersFactory::class),
            ElasticConfigFactory::default(),
        );

        $queryDefinition = self::fromContainer($definitionClass);
        $searchService->getResult(
            $queryDefinition,
            $searchParameters,
        );

        return $searchData;
    }

    /**
     * @param class-string<QueryDefinitionInterface> $definitionClass
     */
    private function matchDefinitionToSnapshot(string $definitionClass, ?SearchParameters $searchParameters = null): void
    {
        $this->assertMatchesJsonSnapshot(
            $this->getDefinitionSearchData($definitionClass, $searchParameters),
        );
    }
}
