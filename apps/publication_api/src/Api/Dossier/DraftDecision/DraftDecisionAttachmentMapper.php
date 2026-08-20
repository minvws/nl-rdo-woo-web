<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;

class DraftDecisionAttachmentMapper
{
    public static function create(
        DraftDecision $draftDecision,
        AttachmentRequestDto $attachmentRequestDto,
    ): DraftDecisionAttachment {
        $attachment = new DraftDecisionAttachment(
            $draftDecision,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );

        $attachment->getFileInfo()->setName($attachmentRequestDto->fileName->toString());
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
