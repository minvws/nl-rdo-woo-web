<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Symfony\Component\Uid\Uuid;

final readonly class IndexAttachmentCommand
{
    private function __construct(
        public Uuid $uuid,
    ) {
    }

    public static function forAttachment(AbstractAttachment $attachment): self
    {
        return new self($attachment->getId());
    }

    public static function forAttachmentEvent(AbstractAttachmentEvent $event): self
    {
        return new self($event->attachmentId);
    }
}
