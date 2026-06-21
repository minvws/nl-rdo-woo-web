<?php

declare(strict_types=1);

namespace Shared\Service\Inventory;

use function array_any;
use function array_key_exists;

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
        return array_any($this->changes, static fn ($changed) => $changed);
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
