<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security;

use Shared\Service\Security\Roles;
use Shared\Tests\Unit\UnitTestCase;

class RolesTest extends UnitTestCase
{
    public function testRoleDetails(): void
    {
        self::assertNotEmpty(Roles::roleDetails());
    }

    public function testGetRoleHierarchyReturnsHierarchyForSuperAdmin(): void
    {
        self::assertNotEmpty(Roles::getRoleHierarchy(Roles::ROLE_SUPER_ADMIN));
    }

    public function testGetRoleHierarchyReturnsEmptyArrayViewAccess(): void
    {
        self::assertEmpty(Roles::getRoleHierarchy(Roles::ROLE_VIEW_ACCESS));
    }

    public function testRoleDescriptions(): void
    {
        $this->assertMatchesJsonSnapshot(Roles::roleDescriptions());
    }
}
