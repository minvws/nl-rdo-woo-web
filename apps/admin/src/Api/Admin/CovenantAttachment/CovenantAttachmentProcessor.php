<?php

declare(strict_types=1);

namespace Admin\Api\Admin\CovenantAttachment;

use Admin\Api\Admin\Attachment\AbstractAttachmentDto;
use Admin\Api\Admin\Attachment\AbstractAttachmentProcessor;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;

final class CovenantAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(CovenantAttachmentDto::class, $entity);
    }
}
