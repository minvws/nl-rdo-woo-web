<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

use PublicationApi\Api\Publication\UploadStatus;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;

readonly class MainDocumentUploadStatusService
{
    public function __construct(
        private UploadEntityRepository $uploadEntityRepository,
    ) {
    }

    public function getUploadStatus(AbstractMainDocument $mainDocument): UploadStatus
    {
        if ($mainDocument->getFileInfo()->isUploaded()) {
            return UploadStatus::PROCESSED;
        }

        $uploadEntity = $this->uploadEntityRepository->findLatestByContextData('mainDocumentId', $mainDocument->getId()->toRfc4122());
        if (! $uploadEntity instanceof UploadEntity) {
            return UploadStatus::UPLOAD_REQUIRED;
        }

        if ($uploadEntity->getStatus() === UploadEntityStatus::VALIDATION_FAILED) {
            return UploadStatus::PROCESSING_FAILED;
        }

        return UploadStatus::PROCESSING;
    }
}
