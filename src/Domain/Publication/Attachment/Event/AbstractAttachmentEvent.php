<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Event;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Utils;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractAttachmentEvent
{
    final public function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
        public string $fileName,
        public string $fileType,
        public string $fileSize,
    ) {
    }

    public static function forAttachment(AbstractAttachment $attachment): static
    {
        return new static(
            $attachment->getDossier()->getId(),
            $attachment->getId(),
            $attachment->getFileInfo()->getName() ?? '',
            $attachment->getFileInfo()->getType() ?? '',
            Utils::getFileSize($attachment),
        );
    }
}
