<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
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
        $attachment->setInternalReference($attachmentRequestDto->internalReference);
        $attachment->setGrounds($attachmentRequestDto->grounds);

        return $attachment;
    }
}
