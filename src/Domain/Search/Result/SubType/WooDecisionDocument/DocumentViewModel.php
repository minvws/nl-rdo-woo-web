<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType\WooDecisionDocument;

use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\FileInfo;
use App\Domain\Search\Result\SubType\SubTypeViewModelInterface;
use App\Entity\Judgement;

readonly class DocumentViewModel implements SubTypeViewModelInterface
{
    public FileInfo $fileInfo;

    public function __construct(
        public string $documentId,
        public string $documentNr,
        string $fileName,
        string $fileSourceType,
        bool $fileUploaded,
        int $fileSize,
        public int $pageCount,
        public Judgement $judgement,
        public ?\DateTimeImmutable $documentDate,
    ) {
        $this->fileInfo = new FileInfo(
            $fileName,
            $fileSourceType,
            $fileUploaded,
            $fileSize,
        );
    }
}
