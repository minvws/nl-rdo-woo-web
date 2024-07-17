<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment;

use App\Service\Storage\EntityStorageService;

readonly class AttachmentFileDeleteStrategy implements AttachmentDeleteStrategyInterface
{
    public function __construct(
        private EntityStorageService $entityStorageService,
    ) {
    }

    public function delete(AbstractAttachment $attachment): void
    {
        $this->entityStorageService->removeFileForEntity($attachment);
    }
}
