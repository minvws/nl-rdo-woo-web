<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\PdfPage;

use App\Domain\Publication\EntityWithFileInfo;

class PdfPageProcessingContext
{
    private EntityWithFileInfo $entity;
    private int $pageNumber;
    private string $workDirPath;
    private string $localDocument;
    private ?string $localPageDocument = null;

    public function __construct(
        EntityWithFileInfo $entity,
        int $pageNumber,
        string $workDirPath,
        string $localDocument,
    ) {
        $this->entity = $entity;
        $this->pageNumber = $pageNumber;
        $this->workDirPath = $workDirPath;
        $this->localDocument = $localDocument;
    }

    public function getEntity(): EntityWithFileInfo
    {
        return $this->entity;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getWorkDirPath(): string
    {
        return $this->workDirPath;
    }

    public function getLocalDocument(): string
    {
        return $this->localDocument;
    }

    public function getLocalPageDocument(): string
    {
        if ($this->localPageDocument === null) {
            throw PdfPageException::forLocalPageDocumentNotSet($this);
        }

        return $this->localPageDocument;
    }

    public function getOptionalLocalPageDocument(): ?string
    {
        return $this->localPageDocument;
    }

    public function setLocalPageDocument(string $localPageDocument): self
    {
        $this->localPageDocument = $localPageDocument;

        return $this;
    }
}
