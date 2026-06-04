<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inquiry\CaseNumbers;
use Shared\ValueObject\PlainDate;

readonly class DocumentMetadata
{
    public function __construct(
        private ?PlainDate $date,
        private string $filename,
        private ?int $familyId,
        private SourceType $sourceType,
        /** @var array<string> */
        private array $grounds,
        private string $id,
        private Judgement $judgement,
        private ?string $period,
        private ?int $threadId,
        private CaseNumbers $caseNumbers,
        private bool $suspended,
        /** @var array<string> */
        private array $links,
        private ?string $remark,
        private string $matter,
        /** @var array<string> */
        private array $refersTo,
    ) {
    }

    public function getDate(): ?PlainDate
    {
        return $this->date;
    }

    public function getFilename(string $documentNr): string
    {
        if ($this->filename === '') {
            // Assume that when we have no filename, we can use the documentNr as filename and its extension is PDF.
            return $documentNr . '.pdf';
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

    public function getId(): string
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

    public function getCaseNumbers(): CaseNumbers
    {
        return $this->caseNumbers;
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

    public function getMatter(): string
    {
        return $this->matter;
    }

    /**
     * @return array<array-key, string>
     */
    public function getRefersTo(): array
    {
        return $this->refersTo;
    }
}
