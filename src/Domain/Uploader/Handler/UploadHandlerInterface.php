<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Handler;

use App\Domain\Uploader\Result\UploadResultInterface;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\UploadRequest;
use League\Flysystem\FilesystemOperator;

interface UploadHandlerInterface
{
    public function handleUploadRequest(UploadEntity $uploadEntity, UploadRequest $request): UploadResultInterface;

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
