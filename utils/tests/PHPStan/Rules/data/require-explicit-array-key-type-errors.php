<?php

declare(strict_types=1);

/**
 * @param array<string> $items
 */
function scenario1(array $items): void
{
}

class ErrorScenarios
{
    /**
     * @return array<int>
     */
    public function scenario2(): array
    {
        return [];
    }

    /**
     * @var array<SomeClass>
     */
    public array $scenario3 = [];

    /**
     * @param array<array<string>> $items
     */
    public function scenario4(array $items): void
    {
    }

    public function scenario5(): void
    {
        $fn = /** @param array<string> $items */ function (array $items): void {};
    }

    public function scenario6(): void
    {
        $fn = /** @param array<string> $items */ fn ($items) => $items;
    }

    /**
     * @return array<string>|null
     */
    public function scenario7(): ?array
    {
        return null;
    }

    /**
     * @param array<string, array{key: array<string>}> $items
     */
    public function scenario8(array $items): void
    {
    }
}
