<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\ElasticConfig;
use App\Service\Elastic\Model\Index;
use Elastic\Elasticsearch\Response\Elasticsearch;

/**
 * Creates and manages Elasticsearch indices and mappings.
 */
class IndexService
{
    public function __construct(
        protected ElasticClientInterface $elastic,
        protected MappingService $mappingService,
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
        try {
            $this->elastic->indices()->putMapping([
                'index' => $indexName,
                'body' => $mapping,
            ]);
        } catch (\Throwable $e) {
            throw $e;
        }

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
     * @return Index[]
     */
    public function list(): array
    {
        $indices = $this->find();

        return $indices;
    }

    /**
     * @return Index[]
     */
    public function get(string $name): array
    {
        $indices = $this->find($name);

        return $indices;
    }

    /**
     * @return Index[]
     */
    public function find(string $name = null): array
    {
        $aliases = $this->listAlias();

        $params = [
            'format' => 'json',
        ];
        if (! is_null($name)) {
            $params['index'] = $name;
        }

        /** @var Elasticsearch $response */
        $response = $this->elastic->cat()->indices($params);

        $indices = [];
        foreach ($response->asArray() as $index) {
            $indexAliases = array_keys($aliases[$index['index']]['aliases'] ?? []);
            $indices[] = new Index(
                name: $index['index'],
                health: $index['health'],
                status: $index['status'],
                docsCount: $index['docs.count'] ?? '??',
                storeSize: $index['store.size'] ?? '??',
                aliases: $indexAliases,
            );
        }

        return $indices;
    }
}
