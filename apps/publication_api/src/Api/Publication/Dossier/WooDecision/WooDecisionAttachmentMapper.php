<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use PublicationApi\Api\Publication\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\FileInfo;
use Shared\ValueObject\ExternalId;

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
        $fileInfo->setName($attachmentRequestDto->fileName);

        $attachment->setFileInfo($fileInfo);
        $attachment->setInternalReference($attachmentRequestDto->internalReference);
        $attachment->setGrounds($attachmentRequestDto->grounds);
        $attachment->setExternalId(ExternalId::create($attachmentRequestDto->externalId));

        return $attachment;
    }
}
