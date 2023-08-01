<?php

declare(strict_types=1);

namespace App;

class Roles
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_ADMIN_USERS = 'ROLE_ADMIN_USERS';
    public const ROLE_ADMIN_DOSSIERS = 'ROLE_ADMIN_DOSSIERS';
    public const ROLE_ADMIN_REQUESTS = 'ROLE_ADMIN_REQUESTS';

    /** @var array|array{role: string, description: string, help: string}[] */
    protected static array $roleInfo = [
        [
            'role' => self::ROLE_ADMIN,
            'description' => 'Global administrator',
            'help' => 'This user is allowed every operation in the administration system.',
        ],
        [
            'role' => self::ROLE_ADMIN_USERS,
            'description' => 'User administrator',
            'help' => 'This user can create/edit users that have access to the administration system.',
        ],
        [
            'role' => self::ROLE_ADMIN_DOSSIERS,
            'description' => 'Dossier administrator',
            'help' => 'This user can create and manage dossiers including the documents.',
        ],
        [
            'role' => self::ROLE_ADMIN_REQUESTS,
            'description' => 'Request administrator',
            'help' => 'This user can manage Woo requests.',
        ],
    ];

    /**
     * @return array{role: string, description: string, help: string}[]
     */
    public static function roleDetails(): array
    {
        return self::$roleInfo;
    }
}
