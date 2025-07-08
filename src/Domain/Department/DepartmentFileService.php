<?php

declare(strict_types=1);

namespace App\Domain\Department;

use App\Domain\Department\Exception\DepartmentAssetNotFound;
use App\Domain\Publication\EntityWithFileInfo;
use App\Entity\Department;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use Webmozart\Assert\Assert;

final readonly class DepartmentFileService
{
    public function __construct(
        private FilesystemOperator $assetsStorage,
        private EntityManagerInterface $doctrine,
    ) {
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
    public function getLogoAsStream(Department $department)
    {
        $fileInfo = $department->getFileInfo();
        if (! $fileInfo->isUploaded()) {
            throw DepartmentAssetNotFound::noLogoFound($department);
        }

        $fullPath = $department->getFileInfo()->getPath();
        Assert::string($fullPath);

        try {
            $stream = $this->assetsStorage->readStream($fullPath);
        } catch (UnableToReadFile $e) {
            throw DepartmentAssetNotFound::noLogoFound($department, $e);
        }

        Assert::resource($stream);

        return $stream;
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
