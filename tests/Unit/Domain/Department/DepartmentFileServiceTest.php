<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Department;

use App\Domain\Department\DepartmentFileService;
use App\Domain\Publication\FileInfo;
use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Domain\Upload\UploadedFile;
use App\Domain\Uploader\AssetsNamer;
use App\Entity\Department;
use App\Service\Storage\LocalFilesystem;
use App\Tests\Unit\UnitTestCase;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;

final class DepartmentFileServiceTest extends UnitTestCase
{
    private AssetsNamer&MockInterface $assetsNamer;
    private FilesystemOperator&MockInterface $assetsStorage;
    private LocalFilesystem&MockInterface $localFilesystem;
    private EntityManagerInterface&MockInterface $doctrine;
    private MimeTypeHelper&MockInterface $mimeTypeHelper;
    private Department&MockInterface $department;
    private UploadedFile&MockInterface $uploadedFile;
    private FileInfo&MockInterface $fileInfo;
    private DepartmentFileService $departmentFileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetsNamer = \Mockery::mock(AssetsNamer::class);
        $this->assetsStorage = \Mockery::mock(FilesystemOperator::class);
        $this->localFilesystem = \Mockery::mock(LocalFilesystem::class);
        $this->doctrine = \Mockery::mock(EntityManagerInterface::class);
        $this->mimeTypeHelper = \Mockery::mock(MimeTypeHelper::class);

        $this->department = \Mockery::mock(Department::class);
        $this->uploadedFile = \Mockery::mock(UploadedFile::class);
        $this->fileInfo = \Mockery::mock(FileInfo::class);

        $this->departmentFileService = new DepartmentFileService(
            $this->assetsNamer,
            $this->assetsStorage,
            $this->localFilesystem,
            $this->doctrine,
            $this->mimeTypeHelper,
        );
    }

    public function testAddDepartmentLogo(): void
    {
        $this->assetsNamer
            ->shouldReceive('getDepartmentLogo')
            ->with($this->department, $this->uploadedFile)
            ->once()
            ->andReturn($remotePath = 'path/to/logo.svg');

        $this->uploadedFile
            ->shouldReceive('getPathname')
            ->andReturn($uploadedFilePath = 'path/to/uploaded/file.svg');

        $stream = fopen('php://temp', 'r');

        $this->localFilesystem
            ->shouldReceive('createStream')
            ->with($uploadedFilePath, 'r')
            ->once()
            ->andReturn($stream);

        $this->assetsStorage
            ->shouldReceive('writeStream')
            ->with($remotePath, $stream);

        $this->department->shouldReceive('getFileInfo')->andReturn($this->fileInfo);

        $this->fileInfo->shouldReceive('setName')->with(null)->once();
        $this->fileInfo->shouldReceive('setSize')->with(0)->once();
        $this->fileInfo->shouldReceive('setType')->with(null)->once();
        $this->fileInfo->shouldReceive('removeFileProperties')->once();

        $this->mimeTypeHelper
            ->shouldReceive('detectMimeType')
            ->with($this->uploadedFile)
            ->once()
            ->andReturn($mimeType = 'image/svg+xml');

        $this->uploadedFile
            ->shouldReceive('getSize')
            ->once()
            ->andReturn($size = 1337);

        $this->fileInfo->shouldReceive('setName')->with('logo.svg')->once();
        $this->fileInfo->shouldReceive('setPath')->with($remotePath)->once();
        $this->fileInfo->shouldReceive('setSize')->with($size)->once();
        $this->fileInfo->shouldReceive('setMimetype')->with($mimeType)->once();
        $this->fileInfo->shouldReceive('setUploaded')->with(true)->once();
        $this->fileInfo->shouldReceive('setType')->with(FileType::VECTOR_IMAGE->value)->once();

        $this->doctrine->shouldReceive('persist')->with($this->department)->once();
        $this->doctrine->shouldReceive('flush')->withNoArgs()->once();

        $this->localFilesystem
            ->shouldReceive('deleteFile')
            ->with($uploadedFilePath)
            ->once()
            ->andReturn(true);

        $returnedDepartment = $this->departmentFileService->addDepartmentLogo($this->department, $this->uploadedFile);

        self::assertSame($this->department, $returnedDepartment);
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

        $this->department->shouldReceive('getFileInfo')->once()->andReturn($this->fileInfo);

        $this->assetsStorage->shouldNotReceive('delete');

        $this->departmentFileService->removeDepartmentLogo($this->department);
    }
}
