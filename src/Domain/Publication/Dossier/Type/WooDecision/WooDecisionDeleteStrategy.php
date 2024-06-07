<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractEntityWithFileInfoDeleteStrategy;
use App\Service\BatchDownloadService;
use App\Service\DocumentService;
use App\Service\Inquiry\InquiryService;
use App\Service\Storage\DocumentStorageService;

readonly class WooDecisionDeleteStrategy extends AbstractEntityWithFileInfoDeleteStrategy
{
    public function __construct(
        DocumentStorageService $storageService,
        private DocumentService $documentService,
        private BatchDownloadService $downloadService,
        private InquiryService $inquiryService,
    ) {
        parent::__construct($storageService);
    }

    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof WooDecision) {
            return;
        }

        foreach ($dossier->getDocuments() as $document) {
            $this->documentService->removeDocumentFromDossier($dossier, $document, false);
        }

        $this->deleteFileForEntity($dossier->getInventory());
        $this->deleteFileForEntity($dossier->getRawInventory());
        $this->deleteFileForEntity($dossier->getDecisionDocument());

        $this->downloadService->removeAllDownloadsForEntity($dossier);
        $this->inquiryService->removeDossierFromInquiries($dossier);
    }
}
