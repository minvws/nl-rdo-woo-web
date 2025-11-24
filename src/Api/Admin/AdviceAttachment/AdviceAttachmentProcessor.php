<?php

declare(strict_types=1);

namespace Shared\Api\Admin\AdviceAttachment;

use Shared\Api\Admin\Attachment\AbstractAttachmentDto;
use Shared\Api\Admin\Attachment\AbstractAttachmentProcessor;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;

final class AdviceAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(AdviceAttachmentDto::class, $entity);
    }
}
