<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Department\LandingPage\ViewModel;

use DateTimeImmutable;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\LandingPage\ViewModel\DepartmentLandingPageViewFactory;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

use function hash;

final class DepartmentLandingPageViewFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = Mockery::mock(UrlGeneratorInterface::class);
    }

    public function testMake(): void
    {
        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('isUploaded')->andReturnTrue();

        $department = Mockery::mock(Department::class);
        $department->expects('getName')->andReturn('my name');
        $department->expects('getId')->times(3)->andReturn($expectedId = Uuid::fromRfc4122('1f040b49-271c-6a7c-9421-c9c7fcf0c37d'));
        $department->expects('getFileInfo')->andReturn($fileInfo);
        $department->expects('getUpdatedAt')->andReturn($updatedAt = new DateTimeImmutable('2023-10-01 12:00:00'));

        $hash = hash('sha256', (string) $updatedAt->getTimestamp());

        $this->urlGenerator
            ->expects('generate')
            ->with('api_uploader_department_remove_logo', ['departmentId' => $expectedId])
            ->andReturn('delete-logo-endpoint');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_admin_department_logo_download', ['id' => $expectedId, 'cacheKey' => $hash])
            ->andReturn('/logo-endpoint');

        $this->urlGenerator
            ->expects('generate')
            ->with('app_admin_upload')
            ->andReturn('upload-logo-endpoint');

        $factory = new DepartmentLandingPageViewFactory($this->urlGenerator);

        $actual = $factory->make($department);

        $this->assertMatchesObjectSnapshot($actual);
    }
}
