<?php

declare(strict_types=1);

namespace App\Domain\Uploader;

use App\Domain\Uploader\Event\UploadCompletedEvent;
use App\Domain\Uploader\Event\UploadValidatedEvent;
use App\Domain\Uploader\Exception\UploadException;
use App\Domain\Uploader\Exception\UploadValidationException;
use App\Domain\Uploader\Handler\UploadHandlerInterface;
use App\Domain\Uploader\Result\UploadCompletedResult;
use App\Domain\Uploader\Result\UploadResultInterface;
use App\Entity\User;
use League\Flysystem\FilesystemOperator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class UploadService
{
    public const string SECURITY_ATTRIBUTE = 'uploader.upload_request';

    public function __construct(
        private UploadHandlerInterface $uploadHandler,
        private EventDispatcherInterface $eventDispatcher,
        private UploadEntityRepository $repository,
        private Security $security,
        private FilesystemOperator $workingCopyStorage,
    ) {
    }

    public function handleUploadRequest(UploadRequest $request): UploadResultInterface
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (! $this->security->isGranted(self::SECURITY_ATTRIBUTE, $request)) {
            throw UploadException::forNotAllowed();
        }

        $uploadEntity = $this->repository->findOrCreate(
            $request->uploadId,
            $request->groupId,
            $user,
            $request->additionalParameters,
        );

        if (! $uploadEntity->getStatus()->isIncomplete()) {
            throw UploadException::forCannotUpload($uploadEntity);
        }

        $result = $this->uploadHandler->handleUploadRequest($uploadEntity, $request);

        if ($result instanceof UploadCompletedResult) {
            $uploadEntity->finishUploading($result->filename, $result->size);

            $this->eventDispatcher->dispatch(
                new UploadCompletedEvent($uploadEntity),
            );
        }

        $this->repository->save($uploadEntity, true);

        return $result;
    }

    public function abortUpload(UploadEntity $uploadEntity): void
    {
        $uploadEntity->abort();

        $this->deleteUploadedFile($uploadEntity);

        $this->repository->save($uploadEntity, true);
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

        $this->repository->save($uploadEntity, true);
    }

    public function passValidation(UploadEntity $uploadEntity, string $mimeType): void
    {
        $uploadEntity->passValidation($mimeType);

        $this->repository->save($uploadEntity, true);

        $this->workingCopyStorage->delete($uploadEntity->getUploadId());

        $this->eventDispatcher->dispatch(
            new UploadValidatedEvent($uploadEntity),
        );
    }

    public function failValidation(UploadEntity $uploadEntity, UploadValidationException $exception): void
    {
        $uploadEntity->failValidation($exception);

        $this->repository->save($uploadEntity, true);

        $this->uploadHandler->deleteUploadedFile($uploadEntity);

        $this->workingCopyStorage->delete($uploadEntity->getUploadId());
    }
}
