<?php

declare(strict_types=1);

namespace App\Service\Security;

use MinVWS\AuditLogger\Contracts\LoggableUser;

readonly class AuditUserDetails implements LoggableUser
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $roles,
        public string $email,
    ) {
    }

    public function getAuditId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
