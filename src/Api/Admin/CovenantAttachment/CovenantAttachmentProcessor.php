<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class CovenantAttachmentProcessor extends AttachmentProcessor
{
    protected function getCreateDtoClass(): string
    {
        return CovenantAttachmentCreateDto::class;
    }

    protected function getUpdateDtoClass(): string
    {
        return CovenantAttachmentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return CovenantAttachmentDto::fromEntity($entity);
    }
}
