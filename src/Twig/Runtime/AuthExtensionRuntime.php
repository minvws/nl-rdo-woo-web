<?php

declare(strict_types=1);

namespace App\Twig\Runtime;

use App\Service\Security\Authorization\AuthorizationMatrix;
use Twig\Extension\RuntimeExtensionInterface;

class AuthExtensionRuntime implements RuntimeExtensionInterface
{
    protected AuthorizationMatrix $authorizationMatrix;

    public function __construct(AuthorizationMatrix $authorizationMatrix)
    {
        $this->authorizationMatrix = $authorizationMatrix;
    }

    public function hasPermission(string $permission): bool
    {
        list($prefix, $permission) = explode('.', $permission, 2);
        if (! $permission) {
            $permission = '';
        }

        return $this->authorizationMatrix->isAuthorized($prefix, $permission);
    }
}
