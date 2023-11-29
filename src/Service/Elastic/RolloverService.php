<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\ElasticConfig;
use App\Message\InitiateElasticRolloverMessage;
use App\Message\SetElasticAliasMessage;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use App\Service\Elastic\Model\Index;
use App\Service\Elastic\Model\RolloverDetails;
use App\Service\Elastic\Model\RolloverParameters;
use App\Service\Search\Object\ObjectHandler;
use Symfony\Component\Messenger\MessageBusInterface;

class RolloverService
{
    public function __construct(
        protected DossierRepository $dossierRepository,
        protected DocumentRepository $documentRepository,
        protected ElasticClientInterface $elastic,
        protected ObjectHandler $objectHandler,
        protected MessageBusInterface $messageBus,
        protected MappingService $mappingService,
    ) {
    }

    /**
     * @param Index[] $indices
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

    public function getDetails(Index $index): RolloverDetails
    {
        $dossierCount = $this->dossierRepository->count([]);
        $documentCounts = $this->documentRepository->getCountAndPageSum();

        $elasticDossierCount = $this->objectHandler->getObjectCount($index->name, 'dossier');
        $elasticDocCount = $this->objectHandler->getObjectCount($index->name, 'document');
        $elasticPageCount = $this->objectHandler->getTotalPageCount($index->name);

        $response = new RolloverDetails(
            index: $index,
            expectedDossierCount: $dossierCount,
            expectedDocCount: $documentCounts->documentCount,
            expectedPageCount: $documentCounts->totalPageCount,
            actualDossierCount: $elasticDossierCount,
            actualDocCount: $elasticDocCount,
            actualPageCount: $elasticPageCount,
        );

        return $response;
    }

    public function makeLive(string $indexName): void
    {
        $this->messageBus->dispatch(
            new SetElasticAliasMessage(
                indexName: $indexName,
                aliasName: ElasticConfig::READ_INDEX,
            )
        );

        $this->messageBus->dispatch(
            new SetElasticAliasMessage(
                indexName: $indexName,
                aliasName: ElasticConfig::WRITE_INDEX,
            )
        );
    }

    public function initiateRollover(RolloverParameters $rollover): void
    {
        $this->messageBus->dispatch(
            new InitiateElasticRolloverMessage(
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
