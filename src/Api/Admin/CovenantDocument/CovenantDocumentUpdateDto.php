<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use App\Api\Admin\Attachment\AttachmentUpdateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;

final class CovenantDocumentUpdateDto extends AttachmentUpdateDto
{
    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return CovenantDocument::getAllowedTypes();
    }
}
