<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\EntityWithFileInfo;

use function md5;
use function serialize;

class AttachmentMapper
{
    public static function updateFromRequestDto(
        AbstractAttachment $attachment,
        AttachmentRequestDto $attachmentRequestDto,
    ): EntityWithFileInfo {
        $currentHash = self::getObjectHash($attachment);

        $attachment->setFormalDate($attachmentRequestDto->formalDate);
        $attachment->setType($attachmentRequestDto->type);
        $attachment->setLanguage($attachmentRequestDto->language);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());

        if (self::getObjectHash($attachment) !== $currentHash) {
            $attachment->getFileInfo()->setUploaded(false);
        }

        return $attachment;
    }

    private static function getObjectHash(AbstractAttachment $attachment): string
    {
        return md5(serialize($attachment));
    }
}
