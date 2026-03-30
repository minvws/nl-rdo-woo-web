<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Attachment;

use PublicationApi\Domain\Upload\AttachmentUploadStatusService;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;

use function array_map;
use function array_values;

readonly class AttachmentResponseDtoFactory
{
    public function __construct(
        private AttachmentUploadStatusService $attachmentUploadStatusService,
    ) {
    }

    /**
     * @param array<array-key,AbstractAttachment> $entities
     *
     * @return list<AttachmentResponseDto>
     */
    public function fromEntities(array $entities): array
    {
        return array_values(array_map($this->fromEntity(...), $entities));
    }

    public function fromEntity(AbstractAttachment $attachment): AttachmentResponseDto
    {
        return new AttachmentResponseDto(
            $attachment->getId(),
            $attachment->getType(),
            $attachment->getLanguage(),
            $attachment->getFormalDate(),
            $attachment->getInternalReference(),
            $attachment->getGrounds(),
            $attachment->getFileInfo()->getName(),
            $attachment->getExternalId(),
            $this->attachmentUploadStatusService->getUploadStatus($attachment),
        );
    }
}
