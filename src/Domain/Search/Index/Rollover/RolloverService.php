<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Shared\Domain\Search\Index\ElasticConfig;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class RolloverService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private MappingService $mappingService,
        private RolloverCounter $counter,
    ) {
    }

    /**
     * @param ElasticIndexDetails[] $indices
     */
    public function getDetailsFromIndices(array $indices): ?RolloverDetails
    {
        foreach ($indices as $index) {
            if (in_array(ElasticConfig::READ_INDEX, $index->aliases)) {
                continue;
            }

            if (! in_array(ElasticConfig::WRITE_INDEX, $index->aliases)) {
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
                aliasName: ElasticConfig::READ_INDEX,
            )
        );

        $this->messageBus->dispatch(
            new SetElasticAliasCommand(
                indexName: $indexName,
                aliasName: ElasticConfig::WRITE_INDEX,
            )
        );
    }

    public function initiateRollover(RolloverParameters $rollover): void
    {
        $this->messageBus->dispatch(
            new InitiateElasticRolloverCommand(
                mappingVersion: $rollover->getMappingVersion(),
                indexName: ElasticConfig::INDEX_PREFIX . date('Ymd-His'),
            )
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
