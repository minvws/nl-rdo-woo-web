<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierDeleteHelper;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Service\BatchDownloadService;
use App\Service\DocumentService;
use App\Service\Inquiry\InquiryService;
use Webmozart\Assert\Assert;

readonly class WooDecisionDeleteStrategy implements DossierDeleteStrategyInterface
{
    public function __construct(
        private DossierDeleteHelper $dossierDeleteHelper,
        private DocumentService $documentService,
        private BatchDownloadService $downloadService,
        private InquiryService $inquiryService,
    ) {
    }

    public function delete(AbstractDossier $dossier): void
    {
        Assert::isInstanceOf($dossier, WooDecision::class);

        foreach ($dossier->getDocuments() as $document) {
            $this->documentService->removeDocumentFromDossier($dossier, $document, false);
        }

        $this->dossierDeleteHelper->deleteFileForEntity($dossier->getInventory());
        $this->dossierDeleteHelper->deleteFileForEntity($dossier->getRawInventory());
        $this->dossierDeleteHelper->deleteFileForEntity($dossier->getDecisionDocument());
        $this->dossierDeleteHelper->deleteAttachments($dossier->getAttachments());

        $this->downloadService->removeAllDownloadsForEntity($dossier);
        $this->inquiryService->removeDossierFromInquiries($dossier);
    }
}
