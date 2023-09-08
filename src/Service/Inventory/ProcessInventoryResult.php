<?php

declare(strict_types=1);

namespace App\Service\Inventory;

class ProcessInventoryResult
{
    /** @var array<int, string[]> */
    private array $rowErrors = [];

    /** @var string[] */
    private array $genericErrors = [];

    public function isSuccessful(): bool
    {
        return count($this->rowErrors) === 0 && count($this->genericErrors) === 0;
    }

    public function addGenericError(string $message): void
    {
        $this->genericErrors[] = $message;
    }

    public function addRowError(int $rowIndex, string $message): void
    {
        if (! isset($this->rowErrors[$rowIndex])) {
            $this->rowErrors[$rowIndex] = [];
        }

        $this->rowErrors[$rowIndex][] = $message;
    }

    /**
     * @return array<int, string[]>
     */
    public function getRowErrors(): array
    {
        return $this->rowErrors;
    }

    /**
     * @return string[]
     */
    public function getGenericErrors(): array
    {
        return $this->genericErrors;
    }

    /**
     * @return array<int|string, string[]>
     */
    public function getAllErrors(): array
    {
        return array_merge(
            ['generic' => $this->genericErrors],
            $this->rowErrors
        );
    }
}
