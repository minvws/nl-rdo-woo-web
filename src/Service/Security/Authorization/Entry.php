<?php

declare(strict_types=1);

namespace App\Service\Security\Authorization;

class Entry
{
    public const PERMISSION_CREATE = 'create';
    public const PERMISSION_READ = 'read';
    public const PERMISSION_UPDATE = 'update';
    public const PERMISSION_DELETE = 'delete';
    public const PERMISSION_EXECUTE = 'execute';

    protected string $prefix;
    /** @var string[] */
    protected array $roles;
    /** @var array<string, bool> */
    protected array $permissions;
    /** @var array<string, bool> */
    protected array $filters;

    /**
     * @param mixed[] $data
     */
    public static function createFrom(array $data): self
    {
        $entry = new self();

        $entry->prefix = $data['prefix'] ?? '';
        $entry->roles = $data['roles'] ?? [];
        $entry->permissions = $data['permissions'] ?? [];
        $entry->filters = $data['filters'] ?? [];

        return $entry;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return bool[]
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return bool[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
