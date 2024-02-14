<?php

declare(strict_types=1);

namespace App\ViewModel;

class DossierCounts
{
    public function __construct(
        private readonly int $documentCount,
        private readonly int $pageCount,
        private readonly int $uploadCount,
    ) {
    }

    public function getDocumentCount(): int
    {
        return $this->documentCount;
    }

    public function hasDocuments(): bool
    {
        return $this->documentCount > 0;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function hasPages(): bool
    {
        return $this->pageCount > 0;
    }

    public function getUploadCount(): int
    {
        return $this->uploadCount;
    }

    public function hasUploads(): bool
    {
        return $this->uploadCount > 0;
    }
}
