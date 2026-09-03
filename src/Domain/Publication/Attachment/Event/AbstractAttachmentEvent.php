<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Event;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Service\Utils\Utils;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractAttachmentEvent
{
    final private function __construct(
        public Uuid $dossierId,
        public Uuid $attachmentId,
        public string $fileName,
        public string $fileType,
        public string $fileSize,
        public bool $fileUpdated,
        public bool $metadataUpdated,
    ) {
    }

    public static function forAttachment(AbstractAttachment $attachment): static
    {
        return self::createFromAttachment($attachment, fileUpdated: false, metadataUpdated: false);
    }

    public static function forAttachmentWithMetadataUpdated(AbstractAttachment $attachment): static
    {
        return self::createFromAttachment($attachment, fileUpdated: false, metadataUpdated: true);
    }

    public static function forAttachmentWithFileUpdated(AbstractAttachment $attachment): static
    {
        return self::createFromAttachment($attachment, fileUpdated: true, metadataUpdated: false);
    }

    public static function forAttachmentWithMetadataAndFileUpdated(AbstractAttachment $attachment): static
    {
        return self::createFromAttachment($attachment, fileUpdated: true, metadataUpdated: true);
    }

    private static function createFromAttachment(
        AbstractAttachment $attachment,
        bool $fileUpdated,
        bool $metadataUpdated,
    ): static {
        return new static(
            $attachment->getDossier()->getId(),
            $attachment->getId(),
            $attachment->getFileInfo()->getName() ?? '',
            $attachment->getFileInfo()->getType() ?? '',
            Utils::getFileSize($attachment),
            $fileUpdated,
            $metadataUpdated,
        );
    }
}
