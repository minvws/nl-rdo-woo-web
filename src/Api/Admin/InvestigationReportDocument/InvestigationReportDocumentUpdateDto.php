<?php

declare(strict_types=1);

namespace App\Api\Admin\InvestigationReportDocument;

use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportDocument;

class InvestigationReportDocumentUpdateDto extends AttachmentUpdateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return InvestigationReportDocument::getAllowedTypes();
    }
}
