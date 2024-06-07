<?php

declare(strict_types=1);

namespace App\Api\Admin\AnnualReportDocument;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportDocument;

class AnnualReportDocumentCreateDto extends AttachmentCreateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return AnnualReportDocument::getAllowedTypes();
    }
}
