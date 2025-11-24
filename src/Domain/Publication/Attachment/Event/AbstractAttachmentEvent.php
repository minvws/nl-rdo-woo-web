<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Event;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Service\Utils\Utils;
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
