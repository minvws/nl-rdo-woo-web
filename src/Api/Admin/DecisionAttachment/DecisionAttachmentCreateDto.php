<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Entity\DecisionAttachment;

final class DecisionAttachmentCreateDto extends AttachmentCreateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return DecisionAttachment::getAllowedTypes();
    }
}
