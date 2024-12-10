<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Roles;

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
