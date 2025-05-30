<?php

declare(strict_types=1);

namespace App\Api\Admin\CovenantAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProcessor;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;

final class CovenantAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(CovenantAttachmentDto::class, $entity);
    }
}
