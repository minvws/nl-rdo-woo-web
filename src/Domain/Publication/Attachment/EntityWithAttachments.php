<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use Doctrine\Common\Collections\Collection;

/**
 * @template TAttachment of AbstractAttachment
 *
 * @property Collection<array-key,TAttachment> $attachments
 */
interface EntityWithAttachments
{
    /**
     * @return Collection<array-key,TAttachment>
     */
    public function getAttachments(): Collection;

    /**
     * @param TAttachment $attachment
     */
    public function addAttachment(AbstractAttachment $attachment): self;

    /**
     * @param TAttachment $attachment
     */
    public function removeAttachment(AbstractAttachment $attachment): self;
}
