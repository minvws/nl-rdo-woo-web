<?php

declare(strict_types=1);

namespace App\Api\Admin\DispositionAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class DispositionAttachmentProcessor extends AttachmentProcessor
{
    protected function getCreateDtoClass(): string
    {
        return DispositionAttachmentCreateDto::class;
    }

    protected function getUpdateDtoClass(): string
    {
        return DispositionAttachmentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return DispositionAttachmentDto::fromEntity($entity);
    }
}
