<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\ElasticIndex\ElasticIndexDetails;
use App\ElasticConfig;
use App\Message\InitiateElasticRolloverMessage;
use App\Message\SetElasticAliasMessage;
use App\Repository\DocumentRepository;
use App\Service\Search\Object\ObjectHandler;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class RolloverService
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private DocumentRepository $documentRepository,
        private ObjectHandler $objectHandler,
        private MessageBusInterface $messageBus,
        private MappingService $mappingService,
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
        $dossierCount = $this->dossierRepository->count([]);
        $documentCounts = $this->documentRepository->getCountAndPageSum();

        $elasticDossierCount = $this->objectHandler->getObjectCount($index->name, ...ElasticDocumentType::getMainTypeValues());
        $elasticDocCount = $this->objectHandler->getObjectCount($index->name, 'document');
        $elasticPageCount = $this->objectHandler->getTotalPageCount($index->name);

        return new RolloverDetails(
            index: $index,
            expectedDossierCount: $dossierCount,
            expectedDocCount: $documentCounts->documentCount,
            expectedPageCount: $documentCounts->totalPageCount,
            actualDossierCount: $elasticDossierCount,
            actualDocCount: $elasticDocCount,
            actualPageCount: $elasticPageCount,
        );
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
