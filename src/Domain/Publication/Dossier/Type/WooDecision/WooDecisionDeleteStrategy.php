<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision;

use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\AbstractEntityWithFileInfoDeleteStrategy;
use Shared\Service\DocumentService;
use Shared\Service\Inquiry\InquiryService;
use Shared\Service\Storage\EntityStorageService;

readonly class WooDecisionDeleteStrategy extends AbstractEntityWithFileInfoDeleteStrategy
{
    public function __construct(
        EntityStorageService $entityStorageService,
        private DocumentService $documentService,
        private BatchDownloadService $downloadService,
        private InquiryService $inquiryService,
    ) {
        parent::__construct($entityStorageService);
    }

    public function delete(AbstractDossier $dossier): void
    {
        if (! $dossier instanceof WooDecision) {
            return;
        }

        foreach ($dossier->getDocuments() as $document) {
            $this->documentService->removeDocumentFromDossier($dossier, $document, false);
        }

        $this->deleteAllFilesForEntity($dossier->getInventory());
        $this->deleteAllFilesForEntity($dossier->getProductionReport());
        $this->deleteAllFilesForEntity($dossier->getProcessRun());

        $this->downloadService->removeAllForScope(
            BatchDownloadScope::forWooDecision($dossier),
        );

        $this->inquiryService->removeDossierFromInquiries($dossier);
    }
}
