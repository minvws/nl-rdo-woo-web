<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantDocument;

use ApiPlatform\Metadata\ApiProperty;
use App\Api\Admin\Attachment\AttachmentCreateDto;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;

final class CovenantDocumentCreateDto extends AttachmentCreateDto
{
    #[ApiProperty(writable: false)]
    public AttachmentType $type = AttachmentType::COVENANT;

    /**
     * @return array<array-key,AttachmentType>
     */
    public function getAllowedAttachmentTypes(): array
    {
        return CovenantDocument::getAllowedTypes();
    }
}
