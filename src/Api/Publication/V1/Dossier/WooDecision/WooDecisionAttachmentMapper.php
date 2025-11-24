<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\WooDecision;

use Shared\Api\Publication\V1\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;

class WooDecisionAttachmentMapper
{
    public static function create(
        WooDecision $wooDecision,
        AttachmentRequestDto $attachmentRequestDto,
    ): WooDecisionAttachment {
        $attachment = new WooDecisionAttachment(
            $wooDecision,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );
        $attachment->setInternalReference($attachmentRequestDto->internalReference);
        $attachment->setGrounds($attachmentRequestDto->grounds);

        return $attachment;
    }
}
