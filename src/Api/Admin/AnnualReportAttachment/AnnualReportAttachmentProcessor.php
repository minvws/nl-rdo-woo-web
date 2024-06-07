<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class AnnualReportAttachmentProcessor extends AttachmentProcessor
{
    protected function getCreateDtoClass(): string
    {
        return AnnualReportAttachmentCreateDto::class;
    }

    protected function getUpdateDtoClass(): string
    {
        return AnnualReportAttachmentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return AnnualReportAttachmentDto::fromEntity($entity);
    }
}
