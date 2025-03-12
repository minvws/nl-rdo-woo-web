<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * @template TAttachment of AbstractAttachment
 *
 * @property Collection<array-key,TAttachment> $attachments
 */
interface EntityWithAttachments
{
    /**
     * @return class-string<TAttachment>
     */
    public function getAttachmentEntityClass(): string;

    /**
     * @return Collection<array-key,TAttachment>
     */
    public function getAttachments(): Collection;

    public function addAttachment(AbstractAttachment $attachment): self;

    public function removeAttachment(AbstractAttachment $attachment): self;
}
