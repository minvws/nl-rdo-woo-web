<?php

declare(strict_types=1);

namespace App\Service\Elastic;

use App\ElasticConfig;
use App\Entity\Dossier;
use App\Repository\DocumentRepository;
use App\Repository\DossierRepository;
use App\Service\Elastic\Model\Index;
use App\Service\Elastic\Model\RolloverDetails;
use App\Service\Search\Object\ObjectHandler;

class RolloverService
{
    public function __construct(
        protected DossierRepository $dossierRepository,
        protected DocumentRepository $documentRepository,
        protected ElasticClientInterface $elastic,
        protected ObjectHandler $objectHandler,
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
        $dossierStatuses = [
            Dossier::STATUS_PREVIEW,
            Dossier::STATUS_PUBLISHED,
        ];
        $expectedDossierCount = $this->dossierRepository->count([
            'status' => $dossierStatuses,
        ]);
        $documentCounts = $this->documentRepository->getCountAndPageSumForStatuses($dossierStatuses);

        $elasticDossierCount = $this->objectHandler->getObjectCount($index->name, 'dossier');
        $elasticDocCount = $this->objectHandler->getObjectCount($index->name, 'document');
        $elasticPageCount = $this->objectHandler->getTotalPageCount($index->name);

        $response = new RolloverDetails(
            index: $index,
            expectedDossierCount: $expectedDossierCount,
            expectedDocumentCount: $documentCounts->documentCount,
            expectedPageCount: $documentCounts->totalPageCount,
            elasticsearchDossierCount: $elasticDossierCount,
            elasticsearchDocumentCount: $elasticDocCount,
            elasticsearchPageCount: $elasticPageCount,
        );

        return $response;
    }
}
