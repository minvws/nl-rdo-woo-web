<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\FileInfo;

class DispositionAttachmentMapper
{
    public static function create(
        Disposition $disposition,
        AttachmentRequestDto $attachmentRequestDto,
    ): DispositionAttachment {
        $attachment = new DispositionAttachment(
            $disposition,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );
        $fileInfo = new FileInfo();
        $fileInfo->setName($attachmentRequestDto->fileName->toString());

        $attachment->setFileInfo($fileInfo);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
