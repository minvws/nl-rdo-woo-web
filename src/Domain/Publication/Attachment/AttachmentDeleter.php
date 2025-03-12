<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class AttachmentDeleter
{
    /**
     * @var iterable<AttachmentDeleteStrategyInterface>
     */
    private iterable $strategies;

    /**
     * @param iterable<AttachmentDeleteStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator('domain.publication.attachment.delete_strategy')]
        iterable $strategies,
    ) {
        $this->strategies = $strategies;
    }

    public function delete(AbstractAttachment $attachment): void
    {
        foreach ($this->strategies as $strategy) {
            $strategy->delete($attachment);
        }
    }
}
