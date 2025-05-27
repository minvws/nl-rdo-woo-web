<?php

declare(strict_types=1);

namespace App\Domain\Department;

use App\Domain\Department\Exception\DepartmentAssetNotFound;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Domain\Upload\UploadedFile;
use App\Domain\Uploader\AssetsNamer;
use App\Entity\Department;
use App\Service\Storage\LocalFilesystem;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Webmozart\Assert\Assert;

final readonly class DepartmentFileService
{
    public function __construct(
        private AssetsNamer $assetsNamer,
        private FilesystemOperator $assetsStorage,
        private LocalFilesystem $localFilesystem,
        private EntityManagerInterface $doctrine,
        private MimeTypeHelper $mimeTypeHelper,
    ) {
    }

    public function addDepartmentLogo(Department $department, UploadedFile $upload): Department
    {
        $remotePath = $this->assetsNamer->getDepartmentLogo($department, $upload);

        $stream = $this->localFilesystem->createStream($upload->getPathname(), 'r');
        Assert::notFalse($stream);

        $this->assetsStorage->writeStream($remotePath, $stream);

        $department = $this->updateDepartment($department, $upload, $remotePath);

        $this->localFilesystem->deleteFile($upload->getPathname());

        return $department;
    }

    public function removeDepartmentLogo(Department $department): void
    {
        if (! $department->getFileInfo()->isUploaded()) {
            return;
        }

        $path = $department->getFileInfo()->getPath();
        Assert::string($path);

        $this->assetsStorage->delete($path);

        $this->unsetFileInfo($department);
        $this->doctrine->persist($department);
        $this->doctrine->flush();
    }

    /**
     * @return resource
     */
    public function getFileAsStream(Department $department, string $file)
    {
        // Because the logo is the only file we have for now, we can do a quick state check
        $fileInfo = $department->getFileInfo();
        if (! $fileInfo->isUploaded() || $fileInfo->getName() !== $file) {
            throw DepartmentAssetNotFound::create($department, $file);
        }

        $fullPath = $this->assetsNamer->getStorageSubpath($department) . basename($file);

        try {
            $stream = $this->assetsStorage->readStream($fullPath);
        } catch (UnableToReadFile $e) {
            throw DepartmentAssetNotFound::create($department, $file, $e);
        }

        Assert::resource($stream);

        return $stream;
    }

    private function updateDepartment(Department $department, UploadedFile $upload, string $remotePath): Department
    {
        $fileInfo = $department->getFileInfo();
        $this->unsetFileInfo($department);

        $fileInfo->setName(basename($remotePath));
        $fileInfo->setPath($remotePath);
        $fileInfo->setSize($upload->getSize());
        $fileInfo->setMimetype($this->mimeTypeHelper->detectMimeType($upload));
        $fileInfo->setUploaded(true);
        $fileInfo->setType(FileType::VECTOR_IMAGE->value);

        $this->doctrine->persist($department);
        $this->doctrine->flush();

        return $department;
    }

    private function unsetFileInfo(EntityWithFileInfo $entity): void
    {
        $fileInfo = $entity->getFileInfo();
        $fileInfo->setName(null);
        $fileInfo->setSize(0);
        $fileInfo->setType(null);
        $fileInfo->removeFileProperties();
    }
}
