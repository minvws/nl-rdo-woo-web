<?php

declare(strict_types=1);

namespace App\Api\Admin\WooDecisionAttachment;

use App\Api\Admin\Attachment\AbstractAttachmentDto;
use App\Api\Admin\Attachment\AbstractAttachmentProcessor;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;

final class WooDecisionAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(WooDecisionAttachmentDto::class, $entity);
    }
}
