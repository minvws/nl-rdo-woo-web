<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security;

use App\Service\Security\Authorization\AuthorizationMatrix;
use App\Service\Security\UserRouteHelper;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class UserRouteHelperTest extends MockeryTestCase
{
    public function testGetDefaultIndexRouteNameWhenAuthorized(): void
    {
        $authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('isAuthorized')
            ->with('dossier', 'read')
            ->andReturn(true);

        $userRouteHelper = new UserRouteHelper($authorizationMatrix);

        self::assertEquals('app_admin_dossiers', $userRouteHelper->getDefaultIndexRouteName());
    }

    public function testGetDefaultIndexRouteNameWhenNotAuthorized(): void
    {
        $authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('isAuthorized')
            ->with('dossier', 'read')
            ->andReturn(false);

        $userRouteHelper = new UserRouteHelper($authorizationMatrix);

        self::assertEquals('app_admin_users', $userRouteHelper->getDefaultIndexRouteName());
    }
}
