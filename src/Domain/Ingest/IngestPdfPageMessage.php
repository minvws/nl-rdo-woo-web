<?php

declare(strict_types=1);

namespace App\Domain\Ingest;

use App\Entity\EntityWithFileInfo;
use Symfony\Component\Uid\Uuid;

final readonly class IngestPdfPageMessage extends IngestMessage
{
    /**
     * @param class-string<EntityWithFileInfo> $entityClass
     */
    public function __construct(
        Uuid $entityId,
        string $entityClass,
        private int $pageNr,
        bool $forceRefresh = false,
    ) {
        parent::__construct(
            $entityId,
            $entityClass,
            $forceRefresh,
        );
    }

    public function getPageNr(): int
    {
        return $this->pageNr;
    }
}
