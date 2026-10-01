<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Service\EnumHelper;

class AbstractAttachmentFactory
{
    public static function createFromRequestDto(
        AbstractDossier&EntityWithAttachments $dossier,
        AttachmentRequestDto $attachmentRequestDto,
    ): AbstractAttachment {
        $class = $dossier->getAttachmentEntityClass();

        $attachment = new $class(
            $dossier,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );

        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());
        $attachment->setGrounds(EnumHelper::getStringValues($attachmentRequestDto->grounds));
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
