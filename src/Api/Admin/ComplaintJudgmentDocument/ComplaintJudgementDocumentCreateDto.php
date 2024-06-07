<?php

declare(strict_types=1);

namespace App\Api\Admin\ComplaintJudgmentDocument;

use ApiPlatform\Metadata\ApiProperty;
use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementDocument;

class ComplaintJudgementDocumentCreateDto extends AttachmentCreateDto
{
    #[ApiProperty(writable: false)]
    public AttachmentType $type = AttachmentType::COMPLAINT;

    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return ComplaintJudgementDocument::getAllowedTypes();
    }
}
