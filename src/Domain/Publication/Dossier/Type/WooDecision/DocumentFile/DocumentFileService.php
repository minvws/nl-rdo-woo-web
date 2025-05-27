<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileSetStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Enum\DocumentFileUploadStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileUploadRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
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

        $documentFileUpload = $this->createNewUpload($documentFileSet, $upload->getOriginalFilename());

        $this->entityStorageService->storeEntity($upload, $documentFileUpload);

        $this->finishUpload($documentFileSet, $documentFileUpload);
    }

    public function createNewUpload(DocumentFileSet $documentFileSet, string $fileName): DocumentFileUpload
    {
        if (! $documentFileSet->getStatus()->isOpenForUploads()) {
            throw DocumentFileSetException::forCannotAddUpload($documentFileSet);
        }

        $documentFileUpload = new DocumentFileUpload($documentFileSet);
        $documentFileUpload->getFileInfo()->setName($fileName);

        $this->documentFileUploadRepository->save($documentFileUpload);

        return $documentFileUpload;
    }

    public function finishUpload(DocumentFileSet $documentFileSet, DocumentFileUpload $upload): DocumentFileUpload
    {
        $documentFileSet->getUploads()->add($upload);
        $this->documentFileSetRepository->save($documentFileSet, true);

        $upload->setStatus(DocumentFileUploadStatus::UPLOADED);
        $this->documentFileUploadRepository->save($upload, true);

        return $upload;
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
        if (! $documentFileSet->canConfirm()) {
            throw DocumentFileSetException::forCannotConfirmUpdates($documentFileSet);
        }

        if ($documentFileSet->getUpdates()->isEmpty()) {
            $this->updateStatus($documentFileSet, DocumentFileSetStatus::NO_CHANGES);

            return;
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

        // For concept dossiers no manual confirmation is needed, immediately confirm and start execution of updates
        if ($documentFileSet->canConfirm()) {
            $this->confirmUpdates($documentFileSet->getDossier());
        } else {
            $this->updateStatus($documentFileSet, DocumentFileSetStatus::NEEDS_CONFIRMATION);
        }
    }

    public function checkProcessingUpdatesCompletion(DocumentFileSet $documentFileSet): void
    {
        if ($this->documentFileSetRepository->countUpdatesToProcess($documentFileSet) > 0) {
            return;
        }

        $this->updateStatus($documentFileSet, DocumentFileSetStatus::COMPLETED);

        $this->dispatcher->dispatchDocumentFileSetProcessedEvent($documentFileSet);
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
