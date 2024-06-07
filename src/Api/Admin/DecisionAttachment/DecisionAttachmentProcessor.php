<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use App\Api\Admin\Attachment\AttachmentDto;
use App\Api\Admin\Attachment\AttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class DecisionAttachmentProcessor extends AttachmentProcessor
{
    protected function getCreateDtoClass(): string
    {
        return DecisionAttachmentCreateDto::class;
    }

    protected function getUpdateDtoClass(): string
    {
        return DecisionAttachmentUpdateDto::class;
    }

    protected function fromEntityToDto(AbstractAttachment $entity): AttachmentDto
    {
        return DecisionAttachmentDto::fromEntity($entity);
    }
}
