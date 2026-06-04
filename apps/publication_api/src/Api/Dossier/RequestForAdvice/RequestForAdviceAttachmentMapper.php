<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\FileInfo;

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
        $fileInfo = new FileInfo();
        $fileInfo->setName($attachmentRequestDto->fileName->toString());

        $attachment->setFileInfo($fileInfo);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
