<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Handler\S3;

use App\Domain\Uploader\Handler\UploadHandlerInterface;
use App\Domain\Uploader\Result\PartialUploadResult;
use App\Domain\Uploader\Result\UploadCompletedResult;
use App\Domain\Uploader\Result\UploadResultInterface;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\UploadRequest;
use League\Flysystem\FilesystemOperator;
use Webmozart\Assert\Assert;

readonly class S3UploadHandler implements UploadHandlerInterface
{
    public function __construct(
        private S3UploadHelper $s3UploadHelper,
    ) {
    }

    public function handleUploadRequest(UploadEntity $uploadEntity, UploadRequest $request): UploadResultInterface
    {
        if ($request->isChunked()) {
            return $this->handleMultipartUpload($uploadEntity, $request);
        }

        return $this->handleSinglePartUpload($request);
    }

    private function handleMultipartUpload(UploadEntity $uploadEntity, UploadRequest $request): UploadResultInterface
    {
        if ($request->chunkIndex === 0) {
            $s3UploadId = $this->s3UploadHelper->createMultipartUpload($request);
            $uploadEntity->setExternalId($s3UploadId);
        } else {
            $s3UploadId = $uploadEntity->getExternalId();
        }

        Assert::notNull($s3UploadId);

        $this->s3UploadHelper->uploadPart($request, $s3UploadId);

        if ($request->hasMoreChunksToFollow()) {
            return PartialUploadResult::create($request);
        }

        $size = $this->s3UploadHelper->completeMultipartUpload($request, $s3UploadId);

        return UploadCompletedResult::create($request, $size);
    }

    private function handleSinglePartUpload(UploadRequest $request): UploadResultInterface
    {
        $this->s3UploadHelper->uploadFile($request);

        $size = filesize($request->uploadedFile->getRealPath());
        Assert::integer($size);

        return UploadCompletedResult::create(
            $request,
            $size,
        );
    }

    public function moveUploadedFileToStorage(
        UploadEntity $uploadEntity,
        FilesystemOperator $filesystem,
        string $targetPath,
    ): void {
        $targetPath = $filesystem->publicUrl($targetPath);
        $this->s3UploadHelper->copyUploadToPath($uploadEntity->getUploadId(), $targetPath);
    }

    public function deleteUploadedFile(UploadEntity $entity): void
    {
        $this->s3UploadHelper->deleteUpload($entity->getUploadId());
    }

    public function copyUploadedFileToFilesystem(
        UploadEntity $uploadEntity,
        ?int $limit,
        FilesystemOperator $targetStorage,
        string $targetPath,
    ): void {
        $targetStorage->writeStream(
            $targetPath,
            $this->s3UploadHelper->readStream($uploadEntity->getUploadId(), $limit)->detach(),
        );
    }
}
