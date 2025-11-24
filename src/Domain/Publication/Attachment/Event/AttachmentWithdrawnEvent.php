<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Event;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class AttachmentWithdrawnEvent
{
    final public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
        public AttachmentWithdrawReason $reason,
        public string $explanation,
    ) {
    }

    public static function forAttachment(AbstractAttachment $attachment): static
    {
        Assert::true($attachment->isWithdrawn());
        Assert::notNull($attachment->getWithdrawReason());
        Assert::notNull($attachment->getWithdrawExplanation());

        return new static(
            $attachment->getDossier()->getId(),
            $attachment->getId(),
            $attachment->getWithdrawReason(),
            $attachment->getWithdrawExplanation(),
        );
    }
}
