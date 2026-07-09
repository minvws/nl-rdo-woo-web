<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;

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

        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
