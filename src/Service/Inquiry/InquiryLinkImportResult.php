<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Exception\TranslatableException;

class InquiryLinkImportResult
{
    /**
     * @var array<array-key,TranslatableException>
     */
    public array $genericExceptions = [];

    /**
     * @var array<int, array<array-key,TranslatableException>>
     */
    public array $rowExceptions = [];

    public function __construct(
        private readonly InquiryChangeset $changeset,
    ) {
    }

    public function addGenericException(TranslatableException $exception): void
    {
        $this->genericExceptions[] = $exception;
    }

    public function addRowException(int $rowNumber, TranslatableException $exception): void
    {
        if (! array_key_exists($rowNumber, $this->rowExceptions)) {
            $this->rowExceptions[$rowNumber] = [];
        }

        $this->rowExceptions[$rowNumber][] = $exception;
    }

    public function getAddedRelationsCount(): int
    {
        return array_reduce(
            $this->changeset->getChanges(),
            static fn (int $count, array $caseChanges) => $count + count($caseChanges[InquiryChangeset::ADD_DOCUMENTS]),
            0,
        );
    }

    public function isSuccessful(): bool
    {
        return ! $this->hasGenericExceptions() && ! $this->hasRowExceptions() && $this->getAddedRelationsCount() > 0;
    }

    public function hasGenericExceptions(): bool
    {
        return count($this->genericExceptions) > 0;
    }

    public function hasRowExceptions(): bool
    {
        return count($this->rowExceptions) > 0;
    }
}
