<?php

declare(strict_types=1);

namespace App\Service\Security\Api;

use Symfony\Component\Security\Core\User\UserInterface;

readonly class ApiUser implements UserInterface
{
    public function __construct(
        private string $commonName,
    ) {
    }

    public function getRoles(): array
    {
        return ['ROLE_API_CLIENT'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->commonName;
    }
}
