<?php

declare(strict_types=1);

namespace Shared\Api\Admin\WooDecisionAttachment;

use Shared\Api\Admin\Attachment\AbstractAttachmentDto;
use Shared\Api\Admin\Attachment\AbstractAttachmentProcessor;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;

final class WooDecisionAttachmentProcessor extends AbstractAttachmentProcessor
{
    protected function fromEntityToDto(AbstractAttachment $entity): AbstractAttachmentDto
    {
        return $this->dtoFactory->make(WooDecisionAttachmentDto::class, $entity);
    }
}
