<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\SourceType;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class DocumentMetadata
{
    public function __construct(
        private readonly ?\DateTimeImmutable $date,
        private readonly string $filename,
        private readonly ?int $familyId,
        private readonly SourceType $sourceType,
        /** @var string[] */
        private readonly array $grounds,
        private readonly string $id,
        private readonly Judgement $judgement,
        private readonly ?string $period,
        private readonly ?int $threadId,
        /** @var string[] */
        private readonly array $caseNumbers,
        private readonly bool $suspended,
        /** @var string[] */
        private readonly array $links,
        private readonly ?string $remark,
        private readonly string $matter,
        /** @var string[] */
        private readonly array $refersTo,
    ) {
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function getFilename(string $documentNr): string
    {
        if (empty($this->filename)) {
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
     * @return string[]
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

    /**
     * @return string[]
     */
    public function getCaseNumbers(): array
    {
        return $this->caseNumbers;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    /**
     * @return string[]
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
     * @return string[]
     */
    public function getRefersTo(): array
    {
        return $this->refersTo;
    }
}
