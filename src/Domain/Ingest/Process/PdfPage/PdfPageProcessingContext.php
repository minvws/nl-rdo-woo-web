<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\PdfPage;

use Shared\Domain\Publication\EntityWithFileInfo;

class PdfPageProcessingContext
{
    private readonly EntityWithFileInfo $entity;
    private readonly int $pageNumber;
    private ?string $pageContents = null;

    public function __construct(
        EntityWithFileInfo $entity,
        int $pageNumber,
    ) {
        $this->entity = $entity;
        $this->pageNumber = $pageNumber;
    }

    public function getEntity(): EntityWithFileInfo
    {
        return $this->entity;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function getPageContents(): string
    {
        if ($this->pageContents === null) {
            throw PdfPageException::forPageContentsNotSet($this);
        }

        return $this->pageContents;
    }

    public function getOptionalPageContents(): ?string
    {
        return $this->pageContents;
    }

    public function setPageContents(string $pageContents): self
    {
        $this->pageContents = $pageContents;

        return $this;
    }
}
