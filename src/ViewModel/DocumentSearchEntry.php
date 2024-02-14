<?php

declare(strict_types=1);

namespace App\ViewModel;

use App\Entity\Judgement;

class DocumentSearchEntry
{
    private readonly FileInfo $fileInfo;

    public function __construct(
        private readonly string $documentId,
        private readonly string $documentNr,
        string $fileName,
        string $fileSourceType,
        bool $fileUploaded,
        int $fileSize,
        private readonly int $pageCount,
        private readonly Judgement $judgement,
        private readonly ?\DateTimeImmutable $documentDate,
    ) {
        $this->fileInfo = new FileInfo(
            $fileName,
            $fileSourceType,
            $fileUploaded,
            $fileSize,
        );
    }

    public function getFileInfo(): FileInfo
    {
        return $this->fileInfo;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getDocumentNr(): string
    {
        return $this->documentNr;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function getJudgement(): Judgement
    {
        return $this->judgement;
    }

    public function getDocumentDate(): ?\DateTimeImmutable
    {
        return $this->documentDate;
    }
}
