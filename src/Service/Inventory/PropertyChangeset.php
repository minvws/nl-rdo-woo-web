<?php

declare(strict_types=1);

namespace App\Service\Inventory;

class PropertyChangeset
{
    /**
     * @var array<array-key, bool>
     */
    private array $changes = [];

    public function compare(string $key, mixed $current, mixed $new): void
    {
        $this->add($key, $current !== $new);
    }

    public function hasChanges(): bool
    {
        foreach ($this->changes as $changed) {
            if ($changed) {
                return true;
            }
        }

        return false;
    }

    public function isChanged(string $key): bool
    {
        if (! array_key_exists($key, $this->changes)) {
            return false;
        }

        return $this->changes[$key];
    }

    public function add(string $key, bool $changed = true): void
    {
        $this->changes[$key] = $changed;
    }
}
