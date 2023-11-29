<?php

declare(strict_types=1);

namespace App\Service\Inventory;

class InventoryChangeset
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public function __construct(
        /** @var array<string, string> */
        private array $changes = [],
    ) {
    }

    public function addCreate(DocumentNumber $documentNumber): void
    {
        $this->changes[$documentNumber->getValue()] = self::ACTION_CREATE;
    }

    public function addUpdate(DocumentNumber $documentNumber): void
    {
        $this->changes[$documentNumber->getValue()] = self::ACTION_UPDATE;
    }

    public function addDelete(string $documentNumber): void
    {
        $this->changes[$documentNumber] = self::ACTION_DELETE;
    }

    public function isEmpty(): bool
    {
        return count($this->changes) === 0;
    }

    public function getAction(DocumentNumber $documentNr): ?string
    {
        return $this->changes[$documentNr->getValue()] ?? null;
    }

    /**
     * @return string[]
     */
    public function getDeletes(): array
    {
        return array_keys(array_filter(
            $this->changes,
            static fn (string $action) => $action === self::ACTION_DELETE
        ));
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        return array_reduce(
            array_keys($this->changes),
            function ($totals, $changeKey) {
                $action = $this->changes[$changeKey];
                $totals[$action]++;

                return $totals;
            },
            [
                self::ACTION_CREATE => 0,
                self::ACTION_UPDATE => 0,
                self::ACTION_DELETE => 0,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->changes;
    }
}
