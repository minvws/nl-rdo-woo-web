<?php

declare(strict_types=1);

namespace Admin\Api\Admin\AdviceAttachment;

use Admin\Api\Admin\Attachment\AbstractAttachmentDto;
use Admin\Api\Admin\Attachment\AbstractAttachmentProcessor;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;

final class AdviceAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(AdviceAttachmentDto::class, $entity);
    }
}
