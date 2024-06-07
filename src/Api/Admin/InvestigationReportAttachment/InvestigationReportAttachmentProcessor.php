<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class InvestigationReportAttachmentProcessor extends AttachmentProcessor
{
    protected function getCreateDtoClass(): string
    {
        return InvestigationReportAttachmentCreateDto::class;
    }

    protected function getUpdateDtoClass(): string
    {
        return InvestigationReportAttachmentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return InvestigationReportAttachmentDto::fromEntity($entity);
    }
}
