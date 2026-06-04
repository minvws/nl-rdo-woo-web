<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Dossier;

use PublicationApi\Api\Attachment\AttachmentRequestDto;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\FileInfo;

class AbstractAttachmentFactory
{
    public static function createFromRequestDto(
        AbstractDossier&EntityWithAttachments $dossier,
        AttachmentRequestDto $attachmentRequestDto,
    ): AbstractAttachment {
        $class = $dossier->getAttachmentEntityClass();

        $annualReportAttachment = new $class(
            $dossier,
            $attachmentRequestDto->formalDate,
            $attachmentRequestDto->type,
            $attachmentRequestDto->language,
        );

        $fileInfo = new FileInfo();
        $fileInfo->setName($attachmentRequestDto->fileName->toString());

        $annualReportAttachment->setFileInfo($fileInfo);
        $annualReportAttachment->setGrounds($attachmentRequestDto->grounds);
        $annualReportAttachment->setExternalId($attachmentRequestDto->externalId);

        return $annualReportAttachment;
    }
}
