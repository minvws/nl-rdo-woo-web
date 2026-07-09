<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;

class CovenantAttachmentMapper
{
    public static function create(
        Covenant $covenant,
        AttachmentRequestDto $attachmentRequestDto,
    ): CovenantAttachment {
        $attachment = new CovenantAttachment(
            $covenant,
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
