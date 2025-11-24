<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

use Shared\Api\Publication\V1\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;

class OtherPublicationAttachmentMapper
{
    public static function create(
        OtherPublication $otherPublication,
        AttachmentRequestDto $attachmentRequestDto,
    ): OtherPublicationAttachment {
        $attachment = new OtherPublicationAttachment(
            $otherPublication,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );
        $attachment->setInternalReference($attachmentRequestDto->internalReference);
        $attachment->setGrounds($attachmentRequestDto->grounds);

        return $attachment;
    }
}
