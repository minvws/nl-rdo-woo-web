<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Command;

use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
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
