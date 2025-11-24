<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Process;

use League\Flysystem\FilesystemOperator;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\EntityWithFileInfo;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Upload\AssetsNamer;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Storage\EntityStorageService;
use Webmozart\Assert\Assert;

readonly class EntityUploadStorer
{
    public function __construct(
        private UploadService $uploadService,
        private FilesystemOperator $documentStorage,
        private FilesystemOperator $assetsStorage,
        private EntityStorageService $entityStorageService,
        private UploadEntityRepository $uploadEntityRepository,
        private AssetsNamer $assetsNamer,
    ) {
    }

    public function storeUploadForEntity(
        UploadEntity $uploadEntity,
        EntityWithFileInfo $targetEntity,
    ): void {
        $filePath = $this->entityStorageService->generateEntityPath($targetEntity, $uploadEntity->getFilename());

        $this->doStore($this->documentStorage, $filePath, $uploadEntity, $targetEntity);
    }

    public function storeUploadForEntityWithSourceTypeAndName(
        EntityWithFileInfo $targetEntity,
        string $uploadId,
    ): void {
        $uploadEntity = $this->uploadEntityRepository->findOneBy(['uploadId' => $uploadId]);
        if (! $uploadEntity instanceof UploadEntity) {
            throw UploadException::forEntityNotFoundByUploadId($uploadId);
        }

        $this->storeUploadForEntity($uploadEntity, $targetEntity);

        $fileType = FileType::fromMimeType($uploadEntity->getMimeType() ?? '');
        $targetEntity->getFileInfo()->setSourceType($fileType ? SourceType::fromFileType($fileType) : SourceType::UNKNOWN);

        $targetEntity->getFileInfo()->setName($uploadEntity->getFilename());
    }

    public function storeDepartmentAssetForEntity(UploadEntity $uploadEntity, Department $targetEntity): void
    {
        $filename = $uploadEntity->getFilename();
        Assert::string($filename);

        $filePath = $this->assetsNamer->getDepartmentLogo($targetEntity, pathinfo($filename, PATHINFO_EXTENSION));

        $this->doStore($this->assetsStorage, $filePath, $uploadEntity, $targetEntity);

        $targetEntity->getFileInfo()->setName($uploadEntity->getFilename());
    }

    private function doStore(
        FilesystemOperator $storage,
        string $filePath,
        UploadEntity $uploadEntity,
        EntityWithFileInfo $targetEntity,
    ): void {
        $this->uploadService->moveUploadToStorage($uploadEntity, $storage, $filePath);

        $size = $uploadEntity->getSize();
        Assert::notNull($size);

        $targetEntity->getFileInfo()->setMimetype($uploadEntity->getMimetype());
        $targetEntity->getFileInfo()->setSize($size);
        $targetEntity->getFileInfo()->setPath($filePath);
        $targetEntity->getFileInfo()->setUploaded(true);
    }
}
