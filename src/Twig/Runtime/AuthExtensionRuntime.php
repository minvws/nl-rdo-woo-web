<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Service\Security\Authorization\AuthorizationMatrix;
use Twig\Extension\RuntimeExtensionInterface;

class AuthExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(protected AuthorizationMatrix $authorizationMatrix)
    {
    }

    public function hasPermission(string $permission): bool
    {
        [$prefix, $permission] = explode('.', $permission, 2);
        if (! $permission) {
            $permission = '';
        }

        return $this->authorizationMatrix->isAuthorized($prefix, $permission);
    }
}
