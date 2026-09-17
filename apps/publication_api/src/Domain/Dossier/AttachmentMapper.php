<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\EntityWithFileInfo;

class AttachmentMapper
{
    public static function updateFromRequestDto(
        AbstractAttachment $attachment,
        AttachmentRequestDto $attachmentRequestDto,
    ): EntityWithFileInfo {
        $attachment->setFormalDate($attachmentRequestDto->formalDate);
        $attachment->setType($attachmentRequestDto->type);
        $attachment->setLanguage($attachmentRequestDto->language);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());

        return $attachment;
    }
}
