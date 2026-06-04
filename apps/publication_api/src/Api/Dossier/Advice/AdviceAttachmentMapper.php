<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\FileInfo;

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

        $fileInfo = new FileInfo();
        $fileInfo->setName($attachmentRequestDto->fileName->toString());

        $attachment->setFileInfo($fileInfo);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
