<?php

declare(strict_types=1);

namespace Admin\Tests\Unit\Domain\Authentication;

use Admin\Domain\Authentication\UserRouteHelper;
use Mockery;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Tests\Unit\UnitTestCase;

class UserRouteHelperTest extends UnitTestCase
{
    public function testGetDefaultIndexRouteNameWhenAuthorized(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('isAuthorized')
            ->with('dossier', 'read')
            ->andReturn(true);

        $userRouteHelper = new UserRouteHelper($authorizationMatrix);

        self::assertEquals('app_admin_dossiers', $userRouteHelper->getDefaultIndexRouteName());
    }

    public function testGetDefaultIndexRouteNameWhenNotAuthorized(): void
    {
        $authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);
        $authorizationMatrix->expects('isAuthorized')
            ->with('dossier', 'read')
            ->andReturn(false);

        $userRouteHelper = new UserRouteHelper($authorizationMatrix);

        self::assertEquals('app_admin_users', $userRouteHelper->getDefaultIndexRouteName());
    }
}
