<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use League\Flysystem\FilesystemOperator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shared\Domain\Upload\Event\UploadCompletedEvent;
use Shared\Domain\Upload\Event\UploadValidatedEvent;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\Result\UploadCompletedResult;
use Shared\Domain\Upload\Result\UploadResultInterface;
use Shared\Service\Security\User;

readonly class UploadService
{
    public const string SECURITY_ATTRIBUTE = 'uploader.upload_request';

    public function __construct(
        private UploadHandlerInterface $uploadHandler,
        private EventDispatcherInterface $eventDispatcher,
        private UploadEntityRepository $uploadEntityRepository,
        private FilesystemOperator $workingCopyStorage,
    ) {
    }

    public function handleUploadRequest(UploadRequest $uploadRequest, ?User $user): UploadResultInterface
    {
        $uploadEntity = $this->uploadEntityRepository->findOrCreate(
            $uploadRequest->uploadId,
            $uploadRequest->groupId,
            $user,
            $uploadRequest->additionalParameters,
        );

        if (! $uploadEntity->getStatus()->isIncomplete()) {
            throw UploadException::forCannotUpload($uploadEntity);
        }

        $result = $this->uploadHandler->handleUpload($uploadEntity, $uploadRequest);

        if ($result instanceof UploadCompletedResult) {
            $uploadEntity->finishUploading($result->filename, $result->size);

            $this->eventDispatcher->dispatch(
                new UploadCompletedEvent($uploadEntity),
            );
        }

        $this->uploadEntityRepository->save($uploadEntity, true);

        return $result;
    }

    public function abortUpload(UploadEntity $uploadEntity): void
    {
        $uploadEntity->abort();

        $this->deleteUploadedFile($uploadEntity);

        $this->uploadEntityRepository->save($uploadEntity, true);
    }

    public function deleteUploadedFile(UploadEntity $uploadEntity): void
    {
        $this->uploadHandler->deleteUploadedFile($uploadEntity);
    }

    public function copyUploadToFilesystem(
        UploadEntity $uploadEntity,
        FilesystemOperator $targetFilesystem,
        string $targetFilename,
        ?int $limit = null,
    ): void {
        if (! $uploadEntity->getStatus()->isDownloadable()) {
            throw UploadException::forCannotDownload($uploadEntity);
        }

        $this->uploadHandler->copyUploadedFileToFilesystem(
            $uploadEntity,
            $limit,
            $targetFilesystem,
            $targetFilename,
        );
    }

    public function moveUploadToStorage(
        UploadEntity $uploadEntity,
        FilesystemOperator $fileSystem,
        string $filePath,
    ): void {
        $uploadEntity->markAsStored();

        $this->uploadHandler->moveUploadedFileToStorage($uploadEntity, $fileSystem, $filePath);

        $this->uploadEntityRepository->save($uploadEntity, true);
    }

    public function passValidation(UploadEntity $uploadEntity, string $mimeType): void
    {
        $uploadEntity->passValidation($mimeType);

        $this->uploadEntityRepository->save($uploadEntity, true);

        $this->workingCopyStorage->delete($uploadEntity->getUploadId());

        $this->eventDispatcher->dispatch(
            new UploadValidatedEvent($uploadEntity),
        );
    }

    public function failValidation(UploadEntity $uploadEntity, UploadValidationException $exception): void
    {
        $uploadEntity->failValidation($exception);

        $this->uploadEntityRepository->save($uploadEntity, true);

        $this->uploadHandler->deleteUploadedFile($uploadEntity);

        $this->workingCopyStorage->delete($uploadEntity->getUploadId());
    }
}
