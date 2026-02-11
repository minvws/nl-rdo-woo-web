<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Disposition;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
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
        $attachment->setInternalReference($attachmentRequestDto->internalReference);
        $attachment->setGrounds($attachmentRequestDto->grounds);

        return $attachment;
    }
}
