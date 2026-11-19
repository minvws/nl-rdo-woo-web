<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Repository\DocumentFileSetRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Upload\UploadContextReader;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;

readonly class DocumentUploadStatusService
{
    public function __construct(
        private UploadEntityRepository $uploadEntityRepository,
        private DocumentFileSetRepository $documentFileSetRepository,
        private WooDecisionRepository $wooDecisionRepository,
        private UploadContextReader $uploadContextReader,
    ) {
    }

    public function getUploadStatus(Document $document): UploadStatus
    {
        if (! $document->shouldBeUploaded()) {
            return UploadStatus::NO_UPLOAD_REQUIRED;
        }

        if ($document->getFileInfo()->isUploaded()) {
            return UploadStatus::PROCESSED;
        }

        $uploadEntity = $this->uploadEntityRepository->findLatestByContextData('documentId', $document->getId()->toRfc4122());
        if (! $uploadEntity instanceof UploadEntity) {
            return UploadStatus::UPLOAD_REQUIRED;
        }

        if ($uploadEntity->getStatus() === UploadEntityStatus::VALIDATION_FAILED) {
            return UploadStatus::PROCESSING_FAILED;
        }

        if ($uploadEntity->getStatus() === UploadEntityStatus::STORED && $this->isFileSetProcessed($uploadEntity)) {
            return UploadStatus::PROCESSING_FAILED;
        }

        return UploadStatus::PROCESSING;
    }

    private function isFileSetProcessed(UploadEntity $uploadEntity): bool
    {
        $dossierId = $this->uploadContextReader->getDossierId($uploadEntity);
        $wooDecision = $this->wooDecisionRepository->find($dossierId);
        if (! $wooDecision instanceof WooDecision) {
            return false;
        }

        return ! $this->documentFileSetRepository->findUncompletedByDossier($wooDecision) instanceof DocumentFileSet;
    }
}
