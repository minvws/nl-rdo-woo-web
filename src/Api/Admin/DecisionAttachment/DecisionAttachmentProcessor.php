<?php

declare(strict_types=1);

namespace App\Api\Admin\DecisionAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProcessor;
use App\Domain\Publication\Attachment\AbstractAttachment;

final class DecisionAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return DecisionAttachmentDto::fromEntity($entity);
    }
}
