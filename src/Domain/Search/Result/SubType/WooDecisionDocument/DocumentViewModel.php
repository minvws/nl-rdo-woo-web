<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Result\SubType\WooDecisionDocument;

use DateTimeImmutable;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel\FileInfo;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Search\Result\SubType\SubTypeViewModelInterface;

readonly class DocumentViewModel implements SubTypeViewModelInterface
{
    public FileInfo $fileInfo;
    public int $pageCount;

    public function __construct(
        public string $documentId,
        public string $documentNr,
        string $fileName,
        public SourceType $fileSourceType,
        bool $fileUploaded,
        int $fileSize,
        ?int $pageCount,
        public Judgement $judgement,
        public ?DateTimeImmutable $documentDate,
    ) {
        $this->fileInfo = new FileInfo(
            $fileName,
            $fileSourceType->value,
            $fileUploaded,
            $fileSize,
        );

        $this->pageCount = $pageCount ?? 0;
    }
}
