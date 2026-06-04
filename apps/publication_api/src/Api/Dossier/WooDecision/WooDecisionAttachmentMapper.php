<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;

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

        $fileInfo = new FileInfo();
        $fileInfo->setName($attachmentRequestDto->fileName->toString());

        $attachment->setFileInfo($fileInfo);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId($attachmentRequestDto->externalId);

        return $attachment;
    }
}
