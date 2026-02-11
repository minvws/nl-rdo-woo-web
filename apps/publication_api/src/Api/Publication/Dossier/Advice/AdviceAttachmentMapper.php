<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Advice;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;

class AdviceAttachmentMapper
{
    public static function create(
        Advice $advice,
        AttachmentRequestDto $attachmentRequestDto,
    ): AdviceAttachment {
        $attachment = new AdviceAttachment(
            $advice,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );
        $attachment->setInternalReference($attachmentRequestDto->internalReference);
        $attachment->setGrounds($attachmentRequestDto->grounds);

        return $attachment;
    }
}
