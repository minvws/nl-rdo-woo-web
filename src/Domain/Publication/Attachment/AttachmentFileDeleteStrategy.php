<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Service\Storage\EntityStorageService;
use App\Service\Storage\ThumbnailStorageService;

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
