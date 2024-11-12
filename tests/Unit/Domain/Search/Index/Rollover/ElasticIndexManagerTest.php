<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use App\Domain\Search\Index\Rollover\MappingService;
use App\ElasticConfig;
use App\Service\Elastic\ElasticClientInterface;
use App\Tests\Unit\UnitTestCase;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Mockery\MockInterface;

class ElasticIndexManagerTest extends UnitTestCase
{
    private ElasticIndexManager $indexManager;
    private ElasticClientInterface&MockInterface $elasticClient;
    private MappingService&MockInterface $mappingService;

    public function setUp(): void
    {
        $this->elasticClient = \Mockery::mock(ElasticClientInterface::class);
        $this->mappingService = \Mockery::mock(MappingService::class);

        $this->indexManager = new ElasticIndexManager(
            $this->elasticClient,
            $this->mappingService,
        );

        parent::setUp();
    }

    public function testCreate(): void
    {
        $name = 'foo';
        $version = 12;

        $this->elasticClient->expects('indices->create')->with(['index' => $name]);
        $this->elasticClient->expects('indices->close')->with(['index' => $name]);

        $this->mappingService->expects('getSettings')->andReturn($settings = ['foo' => 'bar']);
        $this->elasticClient->expects('indices->putSettings')->with(['index' => $name, 'body' => $settings]);

        $this->mappingService->expects('getMapping')->with($version)->andReturn($mapping = ['bar' => 'baz']);
        $this->elasticClient->expects('indices->putMapping')->with(['index' => $name, 'body' => $mapping]);

        $this->elasticClient->expects('indices->open')->with(['index' => $name]);

        $this->indexManager->create($name, $version);
    }

    public function testCreateLatestWithAliases(): void
    {
        $name = 'foo';
        $latestVersion = 12;

        $this->mappingService->expects('getLatestMappingVersion')->andReturn($latestVersion);

        $this->elasticClient->expects('indices->create')->with(['index' => $name]);
        $this->elasticClient->expects('indices->close')->with(['index' => $name]);

        $this->mappingService->expects('getSettings')->andReturn($settings = ['foo' => 'bar']);
        $this->elasticClient->expects('indices->putSettings')->with(['index' => $name, 'body' => $settings]);

        $this->mappingService->expects('getMapping')->with($latestVersion)->andReturn($mapping = ['bar' => 'baz']);
        $this->elasticClient->expects('indices->putMapping')->with(['index' => $name, 'body' => $mapping]);

        $this->elasticClient->expects('indices->open')->with(['index' => $name]);

        $this->elasticClient->expects('indices->updateAliases')->with([
            'body' => [
                'actions' => [
                    ['remove' => ['index' => '*', 'alias' => ElasticConfig::READ_INDEX]],
                    ['add' => ['index' => $name, 'alias' => ElasticConfig::READ_INDEX]],
                ],
            ],
        ]);

        $this->elasticClient->expects('indices->updateAliases')->with([
            'body' => [
                'actions' => [
                    ['remove' => ['index' => '*', 'alias' => ElasticConfig::WRITE_INDEX]],
                    ['add' => ['index' => $name, 'alias' => ElasticConfig::WRITE_INDEX]],
                ],
            ],
        ]);

        $this->indexManager->createLatestWithAliases($name);
    }

    public function testDelete(): void
    {
        $name = 'foo';
        $this->elasticClient->expects('indices->delete')->with(['index' => $name]);

        $this->indexManager->delete($name);
    }

    public function testAliasWithDefaultSingleOptionRemovesExistingAliases(): void
    {
        $name = 'foo';
        $alias = 'bar';

        $this->elasticClient->expects('indices->deleteAlias')->with(['index' => '*', 'name' => $alias]);
        $this->elasticClient->expects('indices->putAlias')->with(['index' => $name, 'name' => $alias]);

        $this->indexManager->alias($name, $alias);
    }

    public function testAliasWithDefaultSingleOptionIgnoreFailingDelete(): void
    {
        $name = 'foo';
        $alias = 'bar';

        $this->elasticClient
            ->expects('indices->deleteAlias')
            ->with(['index' => '*', 'name' => $alias])
            ->andThrow(new \RuntimeException('oops'));

        $this->elasticClient->expects('indices->putAlias')->with(['index' => $name, 'name' => $alias]);

        $this->indexManager->alias($name, $alias);
    }

    public function testAliasWithSingleOptionDisabledDoesNotDeleteExistingAliases(): void
    {
        $name = 'foo';
        $alias = 'bar';

        $this->elasticClient->expects('indices->putAlias')->with(['index' => $name, 'name' => $alias])->andReturn();

        $this->indexManager->alias($name, $alias, false);
    }

    public function testListAlias(): void
    {
        $response = \Mockery::mock(Elasticsearch::class);
        $response->expects('asArray')->andReturn($data = ['foo' => 'bar']);
        $this->elasticClient->expects('indices->getAlias')->with(['index' => '_all'])->andReturn($response);

        self::assertEquals($data, $this->indexManager->listAlias());
    }

    public function testExists(): void
    {
        $name = 'foo';
        $response = \Mockery::mock(Elasticsearch::class);
        $response->expects('asBool')->andReturnTrue();
        $this->elasticClient->expects('indices->exists')->with(['index' => $name])->andReturn($response);

        self::assertTrue($this->indexManager->exists($name));
    }

    public function testFind(): void
    {
        $aliasResponse = \Mockery::mock(Elasticsearch::class);
        $aliasResponse->expects('asArray')->andReturn([
            'indexA' => [
                'aliases' => [
                    'aliasA' => [],
                    'aliasB' => [],
                ],
            ],
            'indexB' => [
                'aliases' => [
                    'aliasC' => [],
                    'aliasD' => [],
                ],
            ],
        ]);
        $this->elasticClient->expects('indices->getAlias')->with(['index' => '_all'])->andReturn($aliasResponse);

        $indicesResponse = \Mockery::mock(Elasticsearch::class);
        $indicesResponse->expects('asArray')->andReturn([
            [
                'health' => 'yellow',
                'status' => 'open',
                'index' => 'indexB',
                'uuid' => 'lLKCaCssTSGgTFvsGO_RnA',
                'pri' => '1',
                'rep' => '1',
                'docs.count' => '2085',
                'docs.deleted' => '68',
                'store.size' => '1.2mb',
                'pri.store.size' => '1.2mb',
            ],
        ]);
        $this->elasticClient->expects('cat->indices')->with(['format' => 'json', 'index' => 'indexB'])->andReturn($indicesResponse);

        $mappingResponse = \Mockery::mock(Elasticsearch::class);
        $mappingResponse->expects('asArray')->andReturn([
            'indexA' => [
                'mappings' => [
                    '_meta' => [
                        '_version' => 13,
                    ],
                ],
            ],
            'indexB' => [
                'mappings' => [
                    '_meta' => [
                        'version' => 11,
                    ],
                ],
            ],
        ]);
        $this->elasticClient->expects('indices->getMapping')->andReturn($mappingResponse);

        $this->assertMatchesObjectSnapshot(
            $this->indexManager->find('indexB')
        );
    }

    public function testList(): void
    {
        $aliasResponse = \Mockery::mock(Elasticsearch::class);
        $aliasResponse->expects('asArray')->andReturn([
            'index123' => [
                'aliases' => [
                    'aliasA' => [],
                    'aliasB' => [],
                ],
            ],
            'index456' => [
                'aliases' => [
                    'aliasC' => [],
                    'aliasD' => [],
                ],
            ],
        ]);
        $this->elasticClient->expects('indices->getAlias')->with(['index' => '_all'])->andReturn($aliasResponse);

        $indicesResponse = \Mockery::mock(Elasticsearch::class);
        $indicesResponse->expects('asArray')->andReturn([
            [
                'health' => 'yellow',
                'status' => 'open',
                'index' => 'index456',
                'uuid' => 'lLKCaCssTSGgTFvsGO_RnA',
                'pri' => '1',
                'rep' => '1',
                'docs.count' => '2085',
                'docs.deleted' => '68',
                'store.size' => '1.2mb',
                'pri.store.size' => '1.2mb',
            ],
            [
                'health' => 'yellow',
                'status' => 'open',
                'index' => 'index123',
                'uuid' => 'lLKCaCssTSGgTFvsGO_RnA',
                'pri' => '1',
                'rep' => '1',
                'docs.count' => '2085',
                'docs.deleted' => '68',
                'store.size' => '1.2mb',
                'pri.store.size' => '1.2mb',
            ],
        ]);
        $this->elasticClient->expects('cat->indices')->with(['format' => 'json'])->andReturn($indicesResponse);

        $mappingResponse = \Mockery::mock(Elasticsearch::class);
        $mappingResponse->expects('asArray')->andReturn([
            'index123' => [
                'mappings' => [
                    '_meta' => [
                        '_version' => 13,
                    ],
                ],
            ],
            'index456' => [
                'mappings' => [
                    '_meta' => [
                        'version' => 11,
                    ],
                ],
            ],
        ]);
        $this->elasticClient->expects('indices->getMapping')->andReturn($mappingResponse);

        $this->assertMatchesObjectSnapshot(
            $this->indexManager->list(),
        );
    }
}
