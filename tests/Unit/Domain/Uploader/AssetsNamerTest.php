<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Uploader;

use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Uploader\AssetsNamer;
use App\Entity\Department;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class AssetsNamerTest extends UnitTestCase
{
    private Department&MockInterface $department;
    private AssetsNamer&MockInterface $assetsNamer;

    protected function setUp(): void
    {
        $this->department = \Mockery::mock(Department::class);

        $this->assetsNamer = \Mockery::mock(AssetsNamer::class)->makePartial()->shouldAllowMockingProtectedMethods();
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

        $file = \Mockery::mock(\SplFileInfo::class);
        $file
            ->shouldReceive('getExtension')
            ->once()
            ->andReturn('png');

        $this->assetsNamer->shouldReceive('getUuid')->once()->andReturn($randomUuid = Uuid::v6());

        $result = $this->assetsNamer->getDepartmentLogo($this->department, $file);

        self::assertEquals(sprintf(
            'department/%s/%s.png',
            $departmentId->toRfc4122(),
            $randomUuid->toRfc4122()
        ), $result);
    }
}
