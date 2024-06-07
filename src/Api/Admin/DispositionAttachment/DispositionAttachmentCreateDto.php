<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionAttachment;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;

final class DispositionAttachmentCreateDto extends AttachmentCreateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return DispositionAttachment::getAllowedTypes();
    }
}
