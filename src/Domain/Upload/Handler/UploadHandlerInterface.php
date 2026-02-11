<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Handler;

use League\Flysystem\FilesystemOperator;
use Shared\Domain\Upload\Result\UploadResultInterface;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadRequest;

interface UploadHandlerInterface
{
    public function handleUpload(UploadEntity $uploadEntity, UploadRequest $request): UploadResultInterface;

    public function moveUploadedFileToStorage(
        UploadEntity $uploadEntity,
        FilesystemOperator $filesystem,
        string $targetPath,
    ): void;

    public function deleteUploadedFile(UploadEntity $entity): void;

    public function copyUploadedFileToFilesystem(
        UploadEntity $uploadEntity,
        ?int $limit,
        FilesystemOperator $targetStorage,
        string $targetPath,
    ): void;
}
