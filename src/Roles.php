<?php

declare(strict_types=1);

namespace App;

class Roles
{
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    public const ROLE_GLOBAL_ADMIN = 'ROLE_GLOBAL_ADMIN';
    public const ROLE_ORGANISATION_ADMIN = 'ROLE_ORGANISATION_ADMIN';
    public const ROLE_DOSSIER_ADMIN = 'ROLE_DOSSIER_ADMIN';
    public const ROLE_VIEW_ACCESS = 'ROLE_VIEW_ACCESS';

    // This is the role hierarchy. It is used to determine which roles a user can assign to other users.
    /** @var array<string, string[]> */
    protected static array $roleHierarchy = [
        self::ROLE_SUPER_ADMIN => [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_GLOBAL_ADMIN,
            self::ROLE_ORGANISATION_ADMIN,
            self::ROLE_DOSSIER_ADMIN,
            self::ROLE_VIEW_ACCESS,
        ],
        self::ROLE_GLOBAL_ADMIN => [
            self::ROLE_GLOBAL_ADMIN,
            self::ROLE_ORGANISATION_ADMIN,
            self::ROLE_DOSSIER_ADMIN,
            self::ROLE_VIEW_ACCESS,
        ],
        self::ROLE_ORGANISATION_ADMIN => [
            self::ROLE_ORGANISATION_ADMIN,
            self::ROLE_DOSSIER_ADMIN,
            self::ROLE_VIEW_ACCESS,
        ],
    ];

    /** @var array|array{role: string, description: string, help: string}[] */
    protected static array $roleInfo = [
        [
            'role' => self::ROLE_SUPER_ADMIN,
            'description' => 'admin.user.role.super_admin',
            'help' => 'admin.user.role.super_admin.desc',
        ],
        [
            'role' => self::ROLE_GLOBAL_ADMIN,
            'description' => 'admin.user.role.global_admin',
            'help' => 'admin.user.role.global_admin.desc',
        ],
        [
            'role' => self::ROLE_ORGANISATION_ADMIN,
            'description' => 'admin.user.role.organisation_admin',
            'help' => 'admin.user.role.organisation_admin.desc',
        ],
        [
            'role' => self::ROLE_DOSSIER_ADMIN,
            'description' => 'admin.user.role.decision_admin',
            'help' => 'admin.user.role.decision_admin.desc',
        ],
        [
            'role' => self::ROLE_VIEW_ACCESS,
            'description' => 'admin.user.role.read_only',
            'help' => 'admin.user.role.read_only.desc',
        ],
    ];

    /**
     * Returns a list of all role details that can be used in the administration system.
     *
     * @return array{role: string, description: string, help: string}[]
     */
    public static function roleDetails(): array
    {
        return self::$roleInfo;
    }

    /**
     * @return array|string[]
     */
    public static function getRoleHierarchy(string $role): array
    {
        return self::$roleHierarchy[$role] ?? [];
    }
}
