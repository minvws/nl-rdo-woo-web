<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportAttachment;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;

final class InvestigationReportAttachmentCreateDto extends AttachmentCreateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return InvestigationReportAttachment::getAllowedTypes();
    }
}
