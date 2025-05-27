<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Exception\ProcessInventoryException;

class InventoryChangeset
{
    public const ADDED = 'create';
    public const UPDATED = 'update';
    public const DELETED = 'delete';
    public const UNCHANGED = 'unchanged';

    public function __construct(
        /** @var array<string, string> */
        private array $documentStatus = [],
    ) {
    }

    public function markAsAdded(DocumentNumber $documentNumber): void
    {
        $this->setDocumentStatus($documentNumber->getValue(), self::ADDED);
    }

    public function markAsUpdated(DocumentNumber $documentNumber): void
    {
        $this->setDocumentStatus($documentNumber->getValue(), self::UPDATED);
    }

    public function markAsDeleted(string $documentNumber): void
    {
        $this->setDocumentStatus($documentNumber, self::DELETED);
    }

    public function markAsUnchanged(DocumentNumber $documentNumber): void
    {
        $this->setDocumentStatus($documentNumber->getValue(), self::UNCHANGED);
    }

    public function hasNoChanges(): bool
    {
        $changes = array_filter(
            $this->documentStatus,
            static fn (string $status) => $status !== self::UNCHANGED,
        );

        return count($changes) === 0;
    }

    public function getStatus(DocumentNumber $documentNr): string
    {
        $key = strtolower($documentNr->getValue());
        if (! array_key_exists($key, $this->documentStatus)) {
            throw new \OutOfBoundsException("DocumentNr $key not found in InventoryChangeset");
        }

        return $this->documentStatus[$key];
    }

    /**
     * @return string[]
     */
    public function getDeleted(): array
    {
        return array_keys(array_filter(
            $this->documentStatus,
            static fn (string $status) => $status === self::DELETED
        ));
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return array_reduce(
            array_keys($this->documentStatus),
            function (array $totals, string $changeKey) {
                $status = $this->documentStatus[$changeKey];
                $totals[$status]++;

                return $totals;
            },
            [
                self::ADDED => 0,
                self::UPDATED => 0,
                self::DELETED => 0,
                self::UNCHANGED => 0,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->documentStatus;
    }

    private function setDocumentStatus(string $documentNumber, string $status): void
    {
        if (key_exists($documentNumber, $this->documentStatus)) {
            throw ProcessInventoryException::forDuplicateDocumentNr($documentNumber);
        }

        $this->documentStatus[strtolower($documentNumber)] = $status;
    }
}
