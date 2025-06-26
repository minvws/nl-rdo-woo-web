<?php

declare(strict_types=1);

namespace App\Domain\Upload\Process;

use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Upload\FileType\FileType;
use App\Domain\Uploader\Exception\UploadException;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\UploadEntityRepository;
use App\Domain\Uploader\UploadService;
use App\Service\Storage\EntityStorageService;
use App\SourceType;
use League\Flysystem\FilesystemOperator;
use Webmozart\Assert\Assert;

readonly class EntityUploadStorer
{
    public function __construct(
        private UploadService $uploadService,
        private FilesystemOperator $documentStorage,
        private EntityStorageService $entityStorageService,
        private UploadEntityRepository $uploadEntityRepository,
    ) {
    }

    public function storeUploadForEntity(
        UploadEntity $uploadEntity,
        EntityWithFileInfo $targetEntity,
    ): void {
        $filePath = $this->entityStorageService->generateEntityPath($targetEntity, $uploadEntity->getFilename());
        $this->uploadService->moveUploadToStorage($uploadEntity, $this->documentStorage, $filePath);

        $size = $uploadEntity->getSize();
        Assert::notNull($size);

        $targetEntity->getFileInfo()->setMimetype($uploadEntity->getMimetype());
        $targetEntity->getFileInfo()->setSize($size);
        $targetEntity->getFileInfo()->setPath($filePath);
        $targetEntity->getFileInfo()->setUploaded(true);
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
}
