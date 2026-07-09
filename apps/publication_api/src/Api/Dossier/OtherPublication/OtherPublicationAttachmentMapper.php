<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
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

        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
