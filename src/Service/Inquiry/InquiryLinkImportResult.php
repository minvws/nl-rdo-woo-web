<?php

declare(strict_types=1);

namespace Shared\Service\Inquiry;

use Shared\Service\Import\ImportError;

use function array_key_exists;
use function array_reduce;
use function count;

class InquiryLinkImportResult
{
    /**
     * @var array<array-key,ImportError>
     */
    public array $genericErrors = [];

    /**
     * @var array<int, array<array-key,ImportError>>
     */
    public array $rowErrors = [];

    private bool $hasMatter = false;

    public function __construct(
        private readonly InquiryChangeset $changeset,
    ) {
    }

    public function hasMatter(): bool
    {
        return $this->hasMatter;
    }

    public function setHasMatter(bool $hasMatter): void
    {
        $this->hasMatter = $hasMatter;
    }

    public function addGenericError(ImportError $importError): void
    {
        $this->genericErrors[] = $importError;
    }

    public function addRowError(int $rowNumber, ImportError $importError): void
    {
        if (! array_key_exists($rowNumber, $this->rowErrors)) {
            $this->rowErrors[$rowNumber] = [];
        }

        $this->rowErrors[$rowNumber][] = $importError;
    }

    public function getAddedRelationsCount(): int
    {
        return array_reduce(
            $this->changeset->getChanges(),
            static fn (int $count, array $inquiryChanges) => $count + count($inquiryChanges[InquiryChangeset::ADD_DOCUMENTS]),
            0,
        );
    }

    public function isSuccessful(): bool
    {
        return ! $this->hasGenericErrors() && ! $this->hasRowErrors() && $this->getAddedRelationsCount() > 0;
    }

    public function hasGenericErrors(): bool
    {
        return count($this->genericErrors) > 0;
    }

    public function hasRowErrors(): bool
    {
        return count($this->rowErrors) > 0;
    }
}
