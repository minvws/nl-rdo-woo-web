<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment;

use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Service\Storage\EntityStorageService;
use Shared\Service\Storage\ThumbnailStorageService;

readonly class AttachmentFileDeleteStrategy implements AttachmentDeleteStrategyInterface
{
    public function __construct(
        private EntityStorageService $entityStorageService,
        private ThumbnailStorageService $thumbnailStorageService,
    ) {
    }

    public function delete(AbstractAttachment $attachment): void
    {
        $this->entityStorageService->deleteAllFilesForEntity($attachment);
        $this->thumbnailStorageService->deleteAllThumbsForEntity($attachment);
    }
}
