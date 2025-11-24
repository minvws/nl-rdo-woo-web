<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument;

use Shared\Service\Storage\EntityStorageService;

readonly class MainDocumentFileDeleteStrategy implements MainDocumentDeleteStrategyInterface
{
    public function __construct(
        private EntityStorageService $entityStorageService,
    ) {
    }

    public function delete(AbstractMainDocument $mainDocument): void
    {
        $this->entityStorageService->deleteAllFilesForEntity($mainDocument);
    }
}
