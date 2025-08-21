<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\PdfPage;

use App\Domain\Publication\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

final readonly class IngestPdfPageCommand
{
    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     */
    public function __construct(
        private Uuid $entityId,
        private string $entityClass,
        private int $pageNr,
    ) {
    }

    public function getEntityId(): Uuid
    {
        return $this->entityId;
    }

    /**
     * @return class-string<EntityWithFileInfo>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getPageNr(): int
    {
        return $this->pageNr;
    }
}
