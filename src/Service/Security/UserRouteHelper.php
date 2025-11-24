<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use Shared\Service\Security\Authorization\AuthorizationMatrix;

readonly class UserRouteHelper
{
    public function __construct(
        private AuthorizationMatrix $authorizationMatrix,
    ) {
    }

    public function getDefaultIndexRouteName(): string
    {
        if ($this->authorizationMatrix->isAuthorized('dossier', 'read')) {
            return 'app_admin_dossiers';
        }

        return 'app_admin_users';
    }
}
