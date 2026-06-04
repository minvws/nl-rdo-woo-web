<?php

declare(strict_types=1);

class ValidScenarios
{
    /** @param array<string, int> $items */
    public function scenario08(array $items): void
    {
    }

    /** @param list<string> $items */
    public function scenario09(array $items): void
    {
    }

    /** @param non-empty-list<int> $items */
    public function scenario10(array $items): void
    {
    }

    public function scenario11(array $items): void
    {
    }

    /** @param array{key: string} $items */
    public function scenario12(array $items): void
    {
    }

    /** @param iterable<string> $items */
    public function scenario13(iterable $items): void
    {
    }

    /** @param array<array-key, string> $items */
    public function scenario14(array $items): void
    {
    }

    /** @param non-empty-array<string> $items */
    public function scenario15(array $items): void
    {
    }
}
