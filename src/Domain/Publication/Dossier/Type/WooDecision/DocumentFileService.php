<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\Exception\DocumentFileSetException;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileSetRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Repository\DocumentFileUploadRepository;
use App\Domain\Upload\UploadedFile;
use App\Service\Storage\EntityStorageService;

readonly class DocumentFileService
{
    public function __construct(
        private DocumentFileDispatcher $dispatcher,
        private DocumentFileSetRepository $documentFileSetRepository,
        private DocumentFileUploadRepository $documentFileUploadRepository,
        private EntityStorageService $entityStorageService,
    ) {
    }

    public function getDocumentFileSet(WooDecision $wooDecision): DocumentFileSet
    {
        $documentFileSet = $this->documentFileSetRepository->findUncompletedByDossier($wooDecision);
        if ($documentFileSet === null) {
            $documentFileSet = new DocumentFileSet($wooDecision);
            $this->documentFileSetRepository->save($documentFileSet, true);
        }

        return $documentFileSet;
    }

    public function addUpload(WooDecision $wooDecision, UploadedFile $upload): void
    {
        $documentFileSet = $this->getDocumentFileSet($wooDecision);
        if (! $documentFileSet->getStatus()->isOpenForUploads()) {
            throw DocumentFileSetException::forCannotAddUpload($documentFileSet);
        }

        $documentFileUpload = new DocumentFileUpload($documentFileSet);
        $documentFileUpload->getFileInfo()->setName($upload->getOriginalFilename());
        $this->documentFileUploadRepository->save($documentFileUpload);

        $this->entityStorageService->storeEntity($upload, $documentFileUpload);

        $documentFileUpload->setStatus(DocumentFileUploadStatus::UPLOADED);
        $this->documentFileUploadRepository->save($documentFileUpload, true);
    }

    public function startProcessingUploads(WooDecision $wooDecision): void
    {
        $documentFileSet = $this->getDocumentFileSet($wooDecision);
        if (! $this->canProcess($documentFileSet)) {
            throw DocumentFileSetException::forCannotStartProcessingUploads($documentFileSet);
        }

        $this->updateStatus($documentFileSet, DocumentFileSetStatus::PROCESSING_UPLOADS);

        $this->dispatcher->dispatchProcessDocumentFileSetUploadsCommand($documentFileSet);
    }

    public function hasUploads(DocumentFileSet $documentFileSet): bool
    {
        return $this->documentFileSetRepository->countUploadsToProcess($documentFileSet) > 0;
    }

    public function canProcess(DocumentFileSet $documentFileSet): bool
    {
        return $documentFileSet->getStatus()->isOpenForUploads() && $this->hasUploads($documentFileSet);
    }

    public function confirmUpdates(WooDecision $wooDecision): void
    {
        if (! ($wooDecision->getStatus()->isConcept() || $wooDecision->getStatus()->isPubliclyAvailableOrScheduled())) {
            throw DocumentFileSetException::forCannotConfirmUpdates($wooDecision);
        }

        $documentFileSet = $this->getDocumentFileSet($wooDecision);
        if (! $documentFileSet->getStatus()->needsConfirmation()) {
            throw DocumentFileSetException::forCannotConfirmUpdates($documentFileSet);
        }

        $this->updateStatus($documentFileSet, DocumentFileSetStatus::CONFIRMED);

        $this->dispatcher->dispatchProcessDocumentFileSetUpdatesCommand($documentFileSet);
    }

    public function rejectUpdates(WooDecision $wooDecision): void
    {
        if (! $wooDecision->getStatus()->isPubliclyAvailableOrScheduled()) {
            throw DocumentFileSetException::forCannotConfirmUpdates($wooDecision);
        }

        $documentFileSet = $this->getDocumentFileSet($wooDecision);
        if (! $documentFileSet->getStatus()->needsConfirmation()) {
            throw DocumentFileSetException::forCannotConfirmUpdates($documentFileSet);
        }

        $this->updateStatus($documentFileSet, DocumentFileSetStatus::REJECTED);
    }

    public function checkProcessingUploadsCompletion(DocumentFileSet $documentFileSet): void
    {
        if ($this->documentFileSetRepository->countUploadsToProcess($documentFileSet) > 0) {
            return;
        }

        $this->updateStatus($documentFileSet, DocumentFileSetStatus::NEEDS_CONFIRMATION);

        // For concept dossiers no manual confirmation is needed, immediately confirm and start execution of updates
        if ($documentFileSet->getDossier()->getStatus()->isConcept()) {
            $this->confirmUpdates($documentFileSet->getDossier());
        }
    }

    public function checkProcessingUpdatesCompletion(DocumentFileSet $documentFileSet): void
    {
        if ($this->documentFileSetRepository->countUpdatesToProcess($documentFileSet) > 0) {
            return;
        }

        $this->updateStatus($documentFileSet, DocumentFileSetStatus::COMPLETED);
    }

    private function updateStatus(DocumentFileSet $documentFileSet, DocumentFileSetStatus $status): void
    {
        try {
            $this->documentFileSetRepository->updateStatusTransactionally($documentFileSet, $status);
        } catch (\Exception) {
            throw DocumentFileSetException::forCannotUpdateStatus($documentFileSet);
        }
    }
}
