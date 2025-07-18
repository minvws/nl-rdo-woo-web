<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\ElasticIndex;

use App\Domain\Search\Index\ElasticConfig;
use App\Domain\Search\Index\Rollover\MappingService;
use App\Service\Elastic\ElasticClientInterface;
use Elastic\Elasticsearch\Response\Elasticsearch;

/**
 * Creates and manages Elasticsearch indices and mappings.
 */
readonly class ElasticIndexManager
{
    public function __construct(
        private ElasticClientInterface $elastic,
        private MappingService $mappingService,
    ) {
    }

    /**
     * Creates a new index with the given mapping version (as found in config/elastic/mapping-vXX.json).
     */
    public function create(string $indexName, int $version): void
    {
        // Create and close so we can set settings and mappings
        $this->elastic->indices()->create(['index' => $indexName]);
        $this->elastic->indices()->close(['index' => $indexName]);

        $settings = $this->mappingService->getSettings();
        $this->elastic->indices()->putSettings([
            'index' => $indexName,
            'body' => $settings,
        ]);

        $mapping = $this->mappingService->getMapping($version);
        $this->elastic->indices()->putMapping([
            'index' => $indexName,
            'body' => $mapping,
        ]);

        // Open the index again for usage
        $this->elastic->indices()->open(['index' => $indexName]);
    }

    public function createLatestWithAliases(string $indexName): void
    {
        $this->create($indexName, $this->mappingService->getLatestMappingVersion());
        $this->switch(ElasticConfig::READ_INDEX, '*', $indexName);
        $this->switch(ElasticConfig::WRITE_INDEX, '*', $indexName);
    }

    /**
     * Deletes given index.
     */
    public function delete(string $indexName): void
    {
        $this->elastic->indices()->delete(['index' => $indexName]);
    }

    /**
     * Creates an alias for the given index. When $single is true, only one index can have the alias at a time.
     */
    public function alias(string $indexName, string $aliasName, bool $single = true): void
    {
        if ($single) {
            try {
                $this->elastic->indices()->deleteAlias(['index' => '*', 'name' => $aliasName]);
            } catch (\Exception) {
                // Ignore
            }
        }

        $this->elastic->indices()->putAlias(['index' => $indexName, 'name' => $aliasName]);
    }

    /**
     * Switch the given alias from $srcIndex to $dstIndex atomically.
     */
    public function switch(string $aliasName, string $srcIndex, string $dstIndex): void
    {
        $this->elastic->indices()->updateAliases([
            'body' => [
                'actions' => [
                    ['remove' => ['index' => $srcIndex, 'alias' => $aliasName]],
                    ['add' => ['index' => $dstIndex, 'alias' => $aliasName]],
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array<string, array<string, mixed>>>
     */
    public function listAlias(): array
    {
        /** @var Elasticsearch $response */
        $response = $this->elastic->indices()->getAlias(['index' => '_all']);

        return $response->asArray();
    }

    /**
     * Returns true when the given index exists.
     */
    public function exists(string $indexName): bool
    {
        /** @var Elasticsearch $response */
        $response = $this->elastic->indices()->exists(['index' => $indexName]);

        return $response->asBool();
    }

    /**
     * @return ElasticIndexDetails[]
     */
    public function list(): array
    {
        $indices = $this->find();

        // ES returns indices in a random order for each request, so manually order them for a consistent list
        usort(
            $indices,
            static fn (ElasticIndexDetails $index1, ElasticIndexDetails $index2) => $index2->name <=> $index1->name
        );

        // Also filter out a special index that is not relevant
        return array_filter(
            $indices,
            static fn (ElasticIndexDetails $index) => $index->name !== 'worker_stats'
        );
    }

    /**
     * @return ElasticIndexDetails[]
     */
    public function find(?string $name = null): array
    {
        $aliases = $this->listAlias();

        $params = [
            'format' => 'json',
        ];
        if (! is_null($name)) {
            $params['index'] = $name;
        }

        /** @var Elasticsearch $indicesResponse */
        $indicesResponse = $this->elastic->cat()->indices($params);

        /** @var Elasticsearch $mappingResponse */
        $mappingResponse = $this->elastic->indices()->getMapping();
        $mappingData = $mappingResponse->asArray();

        $indices = [];
        foreach ($indicesResponse->asArray() as $index) {
            $indexAliases = array_keys($aliases[$index['index']]['aliases'] ?? []);

            $indices[] = new ElasticIndexDetails(
                name: $index['index'],
                health: $index['health'],
                status: $index['status'],
                docsCount: $index['docs.count'] ?? '??',
                storeSize: $index['store.size'] ?? '??',
                mappingVersion: strval($mappingData[$index['index']]['mappings']['_meta']['version'] ?? 'unknown'),
                aliases: $indexAliases,
            );
        }

        return $indices;
    }
}
