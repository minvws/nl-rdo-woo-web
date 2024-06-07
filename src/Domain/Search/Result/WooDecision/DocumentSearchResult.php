<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\FileInfo;
use App\Domain\Search\Result\DossierTypeSearchResultInterface;
use App\Entity\Judgement;

class DocumentSearchResult implements DossierTypeSearchResultInterface
{
    public readonly FileInfo $fileInfo;

    public function __construct(
        public readonly string $documentId,
        public readonly string $documentNr,
        string $fileName,
        string $fileSourceType,
        bool $fileUploaded,
        int $fileSize,
        public readonly int $pageCount,
        public readonly Judgement $judgement,
        public readonly ?\DateTimeImmutable $documentDate,
    ) {
        $this->fileInfo = new FileInfo(
            $fileName,
            $fileSourceType,
            $fileUploaded,
            $fileSize,
        );
    }
}
