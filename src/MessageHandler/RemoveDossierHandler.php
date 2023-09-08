<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\RemoveDossierMessage;
use App\Service\Elastic\ElasticService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Update a dossier data based on info in the database into elasticsearch.
 */
#[AsMessageHandler]
class RemoveDossierHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected ElasticService $elasticService;

    public function __construct(
        EntityManagerInterface $doctrine,
        ElasticService $elasticService,
        LoggerInterface $logger
    ) {
        $this->elasticService = $elasticService;
        $this->logger = $logger;
        $this->doctrine = $doctrine;
    }

    public function __invoke(RemoveDossierMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
        if (! $dossier) {
            // No dossier found for this message
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        // Remove documents that are only attached to this dossier
        $orphanedDocuments = [];
        foreach ($dossier->getDocuments() as $document) {
            if ($document->getDossiers()->count() === 1) {
                $orphanedDocuments[] = $document->getDocumentNr();
                $this->doctrine->remove($document);
            }
        }

        // Remove dossier
        $this->doctrine->remove($dossier);
        $this->doctrine->flush();

        // Remove from elasticsearch
        foreach ($orphanedDocuments as $documentNr) {
            $this->elasticService->removeDocument($documentNr);
        }

        $this->elasticService->removeDossier($dossier);
    }
}
