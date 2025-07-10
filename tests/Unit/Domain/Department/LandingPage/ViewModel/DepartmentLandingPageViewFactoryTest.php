<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department\LandingPage\ViewModel;

use App\Domain\Department\Department;
use App\Domain\Department\LandingPage\ViewModel\DepartmentLandingPageViewFactory;
use App\Domain\Publication\FileInfo;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class DepartmentLandingPageViewFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
    }

    public function testMake(): void
    {
        /** @var FileInfo&MockInterface $fileInfo */
        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();

        /** @var Department&MockInterface $department */
        $department = \Mockery::mock(Department::class);
        $department->shouldReceive('getName')->andReturn('my name');
        $department->shouldReceive('getId')->andReturn($expectedId = Uuid::fromRfc4122('1f040b49-271c-6a7c-9421-c9c7fcf0c37d'));
        $department->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $department->shouldReceive('getUpdatedAt')->andReturn($updatedAt = new \DateTimeImmutable('2023-10-01 12:00:00'));

        $hash = hash('sha256', (string) $updatedAt->getTimestamp());

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('api_uploader_department_remove_logo', ['departmentId' => $expectedId])
            ->andReturn('delete-logo-endpoint');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('app_admin_department_logo_download', ['id' => $expectedId, 'cacheKey' => $hash])
            ->andReturn('/logo-endpoint');

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with('app_admin_upload')
            ->andReturn('upload-logo-endpoint');

        $factory = new DepartmentLandingPageViewFactory($this->urlGenerator);

        $actual = $factory->make($department);

        $this->assertMatchesObjectSnapshot($actual);
    }
}
