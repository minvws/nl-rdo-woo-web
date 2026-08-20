<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PublicationContext;

readonly class DocumentMetadata
{
    public function __construct(
        private ?PlainDate $date,
        private string $filename,
        private ?int $familyId,
        private SourceType $sourceType,
        /** @var array<string> */
        private array $grounds,
        private DocumentId $id,
        private Judgement $judgement,
        private ?string $period,
        private ?int $threadId,
        private InquiryNumbers $inquiryNumbers,
        private bool $suspended,
        /** @var array<string> */
        private array $links,
        private ?string $remark,
        private PublicationContext $publicationContext,
        /** @var array<string> */
        private array $refersTo,
    ) {
    }

    public function getDate(): ?PlainDate
    {
        return $this->date;
    }

    public function getFilename(string $documentNumber): string
    {
        if ($this->filename === '') {
            // Assume that when we have no filename, we can use the documentNumber as filename and its extension is PDF.
            return $documentNumber . '.pdf';
        }

        return $this->filename;
    }

    public function getFamilyId(): ?int
    {
        return $this->familyId;
    }

    public function getSourceType(): SourceType
    {
        return $this->sourceType;
    }

    /**
     * @return array<array-key, string>
     */
    public function getGrounds(): array
    {
        return $this->grounds;
    }

    public function getId(): DocumentId
    {
        return $this->id;
    }

    public function getJudgement(): Judgement
    {
        return $this->judgement;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function getThreadId(): ?int
    {
        return $this->threadId;
    }

    public function getInquiryNumbers(): InquiryNumbers
    {
        return $this->inquiryNumbers;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    /**
     * @return array<array-key, string>
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getPublicationContext(): PublicationContext
    {
        return $this->publicationContext;
    }

    /**
     * @return array<array-key, string>
     */
    public function getRefersTo(): array
    {
        return $this->refersTo;
    }
}
