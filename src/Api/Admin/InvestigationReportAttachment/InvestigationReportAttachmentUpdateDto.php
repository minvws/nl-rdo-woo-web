<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportAttachment;

use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;

final class InvestigationReportAttachmentUpdateDto extends AttachmentUpdateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return InvestigationReportAttachment::getAllowedTypes();
    }
}
