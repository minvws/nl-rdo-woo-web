<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * @template TAttachment of AbstractAttachment
 *
 * @property Collection<array-key,TAttachment> $attachments
 */
trait HasAttachments
{
    /**
     * @return Collection<array-key,TAttachment>
     */
    public function getAttachments(): Collection
    {
        /** @var Collection<array-key,TAttachment> */
        return $this->attachments;
    }

    /**
     * @param TAttachment $attachment
     */
    public function addAttachment(AbstractAttachment $attachment): self
    {
        if (! $this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }

        return $this;
    }

    /**
     * @param TAttachment $attachment
     */
    public function removeAttachment(AbstractAttachment $attachment): self
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }
}
