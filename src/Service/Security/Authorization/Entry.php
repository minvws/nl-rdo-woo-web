<?php

declare(strict_types=1);

namespace Shared\Service\Security\Authorization;

use Webmozart\Assert\Assert;

use function array_key_exists;
use function is_array;
use function is_string;

class Entry
{
    private string $prefix = '';
    /** @var array<array-key, string> */
    private array $roles = [];
    /** @var array<string, bool> */
    private array $permissions = [];
    /** @var array<string, bool> */
    private array $filters = [];

    /**
     * @param array<array-key, mixed> $data
     */
    public static function createFrom(array $data): self
    {
        $entry = new self();

        if (array_key_exists('prefix', $data) && is_string($data['prefix'])) {
            $entry->prefix = $data['prefix'];
        }

        if (array_key_exists('roles', $data) && is_array($data['roles'])) {
            Assert::allString($data['roles']);
            $entry->roles = $data['roles'];
        }

        if (array_key_exists('permissions', $data) && is_array($data['permissions'])) {
            Assert::isMap($data['permissions']);
            Assert::allBoolean($data['permissions']);
            $entry->permissions = $data['permissions'];
        }

        if (array_key_exists('filters', $data) && is_array($data['filters'])) {
            Assert::isMap($data['filters']);
            Assert::allBoolean($data['filters']);
            $entry->filters = $data['filters'];
        }

        return $entry;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return array<array-key, string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<string, bool>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return array<string, bool>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
