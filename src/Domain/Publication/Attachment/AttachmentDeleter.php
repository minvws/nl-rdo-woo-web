<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class AttachmentDeleter
{
    /**
     * @param iterable<AttachmentDeleteStrategyInterface> $strategies
     */
    public function __construct(#[AutowireIterator('domain.publication.attachment.delete_strategy')] private iterable $strategies)
    {
    }

    public function delete(AbstractAttachment $attachment): void
    {
        foreach ($this->strategies as $strategy) {
            $strategy->delete($attachment);
        }
    }
}
