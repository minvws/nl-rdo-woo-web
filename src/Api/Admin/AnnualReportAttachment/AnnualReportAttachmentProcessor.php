<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class AnnualReportAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return AnnualReportAttachmentDto::fromEntity($entity);
    }
}
