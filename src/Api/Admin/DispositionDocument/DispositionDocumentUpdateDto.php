<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionDocument;

use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionDocument;

class DispositionDocumentUpdateDto extends AttachmentUpdateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return DispositionDocument::getAllowedTypes();
    }
}
