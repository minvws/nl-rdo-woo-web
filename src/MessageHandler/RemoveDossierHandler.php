<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Dossier;
use App\Message\RemoveDossierMessage;
use App\Service\BatchDownloadService;
use App\Service\DocumentService;
use App\Service\Elastic\ElasticService;
use App\Service\Inquiry\InquiryService;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Removes a dossier from the database and elasticsearch. Also removes document relations and deletes orphan documents.
 */
#[AsMessageHandler]
class RemoveDossierHandler
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
        private readonly ElasticService $elasticService,
        private readonly LoggerInterface $logger,
        private readonly DocumentService $documentService,
        private readonly DocumentStorageService $storageService,
        private readonly BatchDownloadService $downloadService,
        private readonly InquiryService $inquiryService,
    ) {
    }

    public function __invoke(RemoveDossierMessage $message): void
    {
        $dossier = $this->doctrine->getRepository(Dossier::class)->find($message->getUuid());
        if (! $dossier) {
            $this->logger->warning('No dossier found for this message', [
                'uuid' => $message->getUuid(),
            ]);

            return;
        }

        $this->elasticService->removeDossier($dossier);

        foreach ($dossier->getDocuments() as $document) {
            $this->documentService->removeDocumentFromDossier($dossier, $document, false);
        }

        if ($dossier->getInventory()) {
            $this->storageService->removeFileForEntity($dossier->getInventory());
        }

        if ($dossier->getRawInventory()) {
            $this->storageService->removeFileForEntity($dossier->getRawInventory());
        }

        if ($dossier->getDecisionDocument()) {
            $this->storageService->removeFileForEntity($dossier->getDecisionDocument());
        }

        $this->downloadService->removeAllDownloadsForEntity($dossier);

        $this->inquiryService->removeDossierFromInquiries($dossier);

        $this->doctrine->remove($dossier);
        $this->doctrine->flush();
    }
}
