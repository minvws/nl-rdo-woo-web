<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Upload;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;

readonly class AttachmentUploadStatusService
{
    public function __construct(
        private UploadEntityRepository $uploadEntityRepository,
    ) {
    }

    public function getUploadStatus(AbstractAttachment $attachment): UploadStatus
    {
        if ($attachment->getFileInfo()->isUploaded()) {
            return UploadStatus::PROCESSED;
        }

        $uploadEntity = $this->uploadEntityRepository->findLatestByContextData('attachmentId', $attachment->getId()->toRfc4122());
        if (! $uploadEntity instanceof UploadEntity) {
            return UploadStatus::UPLOAD_REQUIRED;
        }

        if ($uploadEntity->getStatus() === UploadEntityStatus::VALIDATION_FAILED) {
            return UploadStatus::PROCESSING_FAILED;
        }

        return UploadStatus::PROCESSING;
    }
}
