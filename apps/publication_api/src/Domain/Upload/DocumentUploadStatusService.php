<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

use PublicationApi\Api\Publication\UploadStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;

readonly class DocumentUploadStatusService
{
    public function __construct(
        private UploadEntityRepository $uploadEntityRepository,
    ) {
    }

    public function getUploadStatus(Document $document): UploadStatus
    {
        if ($document->isWithdrawn() || $document->isSuspended()) {
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

        return UploadStatus::PROCESSING;
    }
}
