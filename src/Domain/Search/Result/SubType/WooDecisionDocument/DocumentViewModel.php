<?php

declare(strict_types=1);

namespace App\Domain\Search\Result\SubType\WooDecisionDocument;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel\FileInfo;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Search\Result\SubType\SubTypeViewModelInterface;
use App\SourceType;

readonly class DocumentViewModel implements SubTypeViewModelInterface
{
    public FileInfo $fileInfo;

    public function __construct(
        public string $documentId,
        public string $documentNr,
        string $fileName,
        public SourceType $fileSourceType,
        bool $fileUploaded,
        int $fileSize,
        public int $pageCount,
        public Judgement $judgement,
        public ?\DateTimeImmutable $documentDate,
    ) {
        $this->fileInfo = new FileInfo(
            $fileName,
            $fileSourceType->value,
            $fileUploaded,
            $fileSize,
        );
    }
}
