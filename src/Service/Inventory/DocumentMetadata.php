<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Judgement;

/**
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class DocumentMetadata
{
    public function __construct(
        private readonly \DateTimeImmutable $date,
        private readonly string $filename,
        private readonly int $familyId,
        private readonly string $sourceType,
        /** @var string[] */
        private readonly array $grounds,
        private readonly int $id,
        private readonly Judgement $judgement,
        private readonly string $period,
        /** @var string[] */
        private readonly array $subjects,
        private readonly int $threadId,
        /** @var string[] */
        private readonly array $caseNumbers,
        private readonly bool $suspended,
        private readonly ?string $link,
        private readonly ?string $remark,
        private readonly ?string $matter,
    ) {
    }

    public function getDate(): \DateTimeImmutable
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

    public function getFamilyId(): int
    {
        return $this->familyId;
    }

    public function getSourceType(): string
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getJudgement(): Judgement
    {
        return $this->judgement;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    /**
     * @return string[]
     */
    public function getSubjects(): array
    {
        return $this->subjects;
    }

    public function getThreadId(): int
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

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function getMatter(): ?string
    {
        return $this->matter;
    }
}
