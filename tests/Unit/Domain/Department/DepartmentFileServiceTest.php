<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department;

use App\Domain\Department\DepartmentFileService;
use App\Domain\Department\Exception\DepartmentAssetNotFound;
use App\Domain\Publication\FileInfo;
use App\Entity\Department;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

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

        $this->assetsStorage = \Mockery::mock(FilesystemOperator::class);
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);

        $this->department = \Mockery::mock(Department::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);

        $this->departmentFileService = new DepartmentFileService(
            $this->assetsStorage,
            $this->doctrine,
        );
    }

    public function testRemoveDepartmentLogo(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();

        $this->department->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->fileInfo->shouldReceive('getPath')->andReturn($path = 'path/to/logo.svg');
        $this->department->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->assetsStorage->shouldReceive('delete')->with($path);

        $this->fileInfo->shouldReceive('setName')->with(null)->once();
        $this->fileInfo->shouldReceive('setSize')->with(0)->once();
        $this->fileInfo->shouldReceive('setType')->with(null)->once();
        $this->fileInfo->shouldReceive('removeFileProperties')->once();

        $this->doctrine->shouldReceive('persist')->with($this->department)->once();
        $this->doctrine->shouldReceive('flush')->withNoArgs()->once();

        $this->departmentFileService->removeDepartmentLogo($this->department);
    }

    public function testRemoveDepartmentLogoDoesNothingIfNotUploaded(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->once()->andReturnFalse();

        $this->department->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->assetsStorage->shouldNotReceive('delete');

        $this->departmentFileService->removeDepartmentLogo($this->department);
    }

    public function testGetLogoAsStream(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();
        $this->fileInfo->shouldReceive('getPath')->once()->andReturn($path = 'path/to/logo.svg');

        $this->department->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $stream = fopen('php://temp', 'r+');

        $this->assetsStorage
            ->shouldReceive('readStream')
            ->with($path)
            ->once()
            ->andReturn($stream);

        $result = $this->departmentFileService->getLogoAsStream($this->department);

        self::assertSame($stream, $result);
    }

    public function testGetLogoAsStreamThrowsNoLogFoundExceptionIfNotUploaded(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->once()->andReturnFalse();

        $this->department->shouldReceive('getFileInfo')->andReturn($this->fileInfo);
        $this->department
            ->shouldReceive('getId')
            ->andReturn(Uuid::fromString('1f0535b7-189d-60fc-9b87-cf947e5e653e'));

        self::expectExceptionObject(
            DepartmentAssetNotFound::noLogoFound($this->department)
        );

        $this->departmentFileService->getLogoAsStream($this->department);
    }

    public function testGetLogoAsStreamThrowsNoLogFoundExceptionIfFileCannotBeFound(): void
    {
        $this->fileInfo->shouldReceive('isUploaded')->once()->andReturnTrue();
        $this->fileInfo->shouldReceive('getPath')->once()->andReturn($path = 'path/to/logo.svg');

        $this->department->shouldReceive('getFileInfo')->once()->andReturn($this->fileInfo);

        $this->assetsStorage
            ->shouldReceive('readStream')
            ->with($path)
            ->once()
            ->andThrow(new UnableToReadFile('File not found'));

        $this->department->shouldReceive('getFileInfo')->once()->andReturn($this->fileInfo);
        $this->department
            ->shouldReceive('getId')
            ->andReturn(Uuid::fromString('1f0535b7-189d-60fc-9b87-cf947e5e653e'));

        self::expectExceptionObject(
            DepartmentAssetNotFound::noLogoFound($this->department)
        );

        $this->departmentFileService->getLogoAsStream($this->department);
    }
}
