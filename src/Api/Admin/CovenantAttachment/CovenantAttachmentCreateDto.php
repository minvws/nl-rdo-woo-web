<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;

final class CovenantAttachmentCreateDto extends AttachmentCreateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return CovenantAttachment::getAllowedTypes();
    }
}
