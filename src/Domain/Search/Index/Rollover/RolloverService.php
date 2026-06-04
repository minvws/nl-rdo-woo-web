<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use Symfony\Component\Messenger\MessageBusInterface;

use function date;
use function in_array;

readonly class RolloverService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private MappingService $mappingService,
        private RolloverCounter $counter,
        private ElasticConfig $elasticConfig,
    ) {
    }

    /**
     * @param array<array-key, ElasticIndexDetails> $indices
     */
    public function getDetailsFromIndices(array $indices): ?RolloverDetails
    {
        foreach ($indices as $index) {
            if (in_array($this->elasticConfig->readIndex, $index->aliases)) {
                continue;
            }

            if (! in_array($this->elasticConfig->writeIndex, $index->aliases)) {
                continue;
            }

            return $this->getDetails($index);
        }

        return null;
    }

    public function getDetails(ElasticIndexDetails $index): RolloverDetails
    {
        return new RolloverDetails(
            index: $index,
            counts: $this->counter->getEntityCounts($index),
        );
    }

    public function makeLive(string $indexName): void
    {
        $this->messageBus->dispatch(
            new SetElasticAliasCommand(
                indexName: $indexName,
                aliasName: $this->elasticConfig->readIndex,
            ),
        );

        $this->messageBus->dispatch(
            new SetElasticAliasCommand(
                indexName: $indexName,
                aliasName: $this->elasticConfig->writeIndex,
            ),
        );
    }

    public function initiateRollover(RolloverParameters $rollover): void
    {
        $this->messageBus->dispatch(
            new InitiateElasticRolloverCommand(
                mappingVersion: $rollover->getMappingVersion(),
                indexName: $this->elasticConfig->indexPrefix . date('Ymd-His'),
            ),
        );
    }

    public function getDefaultRolloverParameters(): RolloverParameters
    {
        $latestMappingVersion = $this->mappingService->getLatestMappingVersion();

        return new RolloverParameters(
            mappingVersion: $latestMappingVersion,
        );
    }
}
