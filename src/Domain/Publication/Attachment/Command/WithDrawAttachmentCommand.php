<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Command;

use Shared\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Symfony\Component\Uid\Uuid;

readonly class WithDrawAttachmentCommand
{
    public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
        public AttachmentWithdrawReason $reason,
        public string $explanation,
    ) {
    }
}
