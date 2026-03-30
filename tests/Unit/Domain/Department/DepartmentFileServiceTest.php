<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Department;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentFileService;
use Shared\Domain\Department\Exception\DepartmentAssetNotFound;
use Shared\Domain\Publication\FileInfo;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function fopen;

final class DepartmentFileServiceTest extends UnitTestCase
{
    private FilesystemOperator&MockInterface $assetsStorage;
    private EntityManagerInterface&MockInterface $doctrine;
    private Department&MockInterface $department;
    private FileInfo&MockInterface $fileInfo;
    private DepartmentFileService $departmentFileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetsStorage = Mockery::mock(FilesystemOperator::class);
        $this->doctrine = Mockery::mock(EntityManagerInterface::class);

        $this->department = Mockery::mock(Department::class);
        $this->fileInfo = Mockery::mock(FileInfo::class);

        $this->departmentFileService = new DepartmentFileService(
            $this->assetsStorage,
            $this->doctrine,
        );
    }

    public function testRemoveDepartmentLogo(): void
    {
        $this->fileInfo->expects('isUploaded')->andReturnTrue();

        $this->department->expects('getFileInfo')->times(2)->andReturn($this->fileInfo);

        $this->fileInfo->expects('getPath')->andReturn($path = 'path/to/logo.svg');
        $this->department->expects('getFileInfo')->andReturn($this->fileInfo);

        $this->assetsStorage->expects('delete')->with($path);

        $this->fileInfo->expects('setName')->with(null);
        $this->fileInfo->expects('setSize')->with(0);
        $this->fileInfo->expects('setType')->with(null);
        $this->fileInfo->expects('removeFileProperties');

        $this->doctrine->expects('persist')->with($this->department);
        $this->doctrine->expects('flush')->withNoArgs();

        $this->departmentFileService->removeDepartmentLogo($this->department);
    }

    public function testRemoveDepartmentLogoDoesNothingIfNotUploaded(): void
    {
        $this->fileInfo->expects('isUploaded')->andReturnFalse();

        $this->department->expects('getFileInfo')->andReturn($this->fileInfo);

        $this->assetsStorage->shouldNotReceive('delete');

        $this->departmentFileService->removeDepartmentLogo($this->department);
    }

    public function testGetLogoAsStream(): void
    {
        $this->fileInfo->expects('isUploaded')->andReturnTrue();
        $this->fileInfo->expects('getPath')->andReturn($path = 'path/to/logo.svg');

        $this->department->expects('getFileInfo')->times(2)->andReturn($this->fileInfo);

        $stream = fopen('php://temp', 'r+');

        $this->assetsStorage
            ->expects('readStream')
            ->with($path)

            ->andReturn($stream);

        $result = $this->departmentFileService->getLogoAsStream($this->department);

        self::assertSame($stream, $result);
    }

    public function testGetLogoAsStreamThrowsNoLogFoundExceptionIfNotUploaded(): void
    {
        $this->fileInfo->expects('isUploaded')->andReturnFalse();

        $this->department->expects('getFileInfo')->andReturn($this->fileInfo);
        $this->department
            ->expects('getId')
            ->times(2)
            ->andReturn(Uuid::fromString('1f0535b7-189d-60fc-9b87-cf947e5e653e'));

        self::expectExceptionObject(
            DepartmentAssetNotFound::noLogoFound($this->department)
        );

        $this->departmentFileService->getLogoAsStream($this->department);
    }

    public function testGetLogoAsStreamThrowsNoLogFoundExceptionIfFileCannotBeFound(): void
    {
        $this->fileInfo->expects('isUploaded')->andReturnTrue();
        $this->fileInfo->expects('getPath')->andReturn($path = 'path/to/logo.svg');

        $this->department->expects('getFileInfo')->andReturn($this->fileInfo);

        $this->assetsStorage
            ->expects('readStream')
            ->with($path)

            ->andThrow(new UnableToReadFile('File not found'));

        $this->department->expects('getFileInfo')->andReturn($this->fileInfo);
        $this->department
            ->expects('getId')
            ->times(2)
            ->andReturn(Uuid::fromString('1f0535b7-189d-60fc-9b87-cf947e5e653e'));

        self::expectExceptionObject(
            DepartmentAssetNotFound::noLogoFound($this->department)
        );

        $this->departmentFileService->getLogoAsStream($this->department);
    }
}
