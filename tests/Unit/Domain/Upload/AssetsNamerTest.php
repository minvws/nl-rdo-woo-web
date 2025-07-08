<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Upload;

use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Upload\AssetsNamer;
use App\Entity\Department;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class AssetsNamerTest extends UnitTestCase
{
    private Department&MockInterface $department;
    private AssetsNamer $assetsNamer;

    protected function setUp(): void
    {
        $this->department = \Mockery::mock(Department::class);

        $this->assetsNamer = new AssetsNamer();
    }

    public function testGetStorageSubpatForDepartment(): void
    {
        $this->department
            ->shouldReceive('getId')
            ->once()
            ->andReturn($uuid = Uuid::v6());

        $result = $this->assetsNamer->getStorageSubpath($this->department);

        self::assertEquals(sprintf('department/%s/', $uuid->toRfc4122()), $result);
    }

    public function testGetStorageSubpatForMisc(): void
    {
        $entityWithFileInfo = \Mockery::mock(EntityWithFileInfo::class);

        $entityWithFileInfo
            ->shouldReceive('getId')
            ->once()
            ->andReturn($uuid = Uuid::v6());

        $result = $this->assetsNamer->getStorageSubpath($entityWithFileInfo);

        self::assertEquals(sprintf('misc/%s/', $uuid->toRfc4122()), $result);
    }

    public function testGetDepartmntLogo(): void
    {
        $this->department
            ->shouldReceive('getId')
            ->once()
            ->andReturn($departmentId = Uuid::v6());

        $extension = 'png';

        $result = $this->assetsNamer->getDepartmentLogo($this->department, $extension);

        self::assertEquals(sprintf(
            'department/%s/logo.%s',
            $departmentId->toRfc4122(),
            $extension,
        ), $result);
    }
}
