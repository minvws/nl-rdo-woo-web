<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;

class RequestForAdviceAttachmentMapper
{
    public static function create(
        RequestForAdvice $requestForAdvice,
        AttachmentRequestDto $attachmentRequestDto,
    ): RequestForAdviceAttachment {
        $attachment = new RequestForAdviceAttachment(
            $requestForAdvice,
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
