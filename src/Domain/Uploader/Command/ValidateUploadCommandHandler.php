<?php

declare(strict_types=1);

namespace App\Domain\Uploader\Command;

use App\Domain\Upload\AntiVirus\ClamAvFileScanner;
use App\Domain\Upload\FileType\MimeTypeHelper;
use App\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use App\Domain\Uploader\Exception\UploadException;
use App\Domain\Uploader\Exception\UploadValidationException;
use App\Domain\Uploader\UploadEntity;
use App\Domain\Uploader\UploadEntityRepository;
use App\Domain\Uploader\UploadService;
use League\Flysystem\FilesystemOperator;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class ValidateUploadCommandHandler
{
    public function __construct(
        private UploadEntityRepository $repository,
        private UploadService $uploadService,
        private FilesystemOperator $workingCopyStorage,
        private ClamAvFileScanner $clamAvFileScanner,
        private MimeTypeHelper $mimeTypeHelper,
        private SevenZipFileStrategy $sevenZipFileStrategy,
    ) {
    }

    public function __invoke(ValidateUploadCommand $message): void
    {
        $uploadEntity = $this->loadUploadEntity($message->uuid);

        $scanSizeExceeded = $uploadEntity->getSize() > $this->clamAvFileScanner->getFileSizeLimit();

        $this->uploadService->copyUploadToFilesystem(
            $uploadEntity,
            $this->workingCopyStorage,
            $uploadEntity->getUploadId(),
            $scanSizeExceeded ? $this->mimeTypeHelper::SAMPLE_SIZE : null,
        );

        try {
            $mimeType = $this->validateMimetype($uploadEntity);
            $this->scanFileContents($uploadEntity, $mimeType, $scanSizeExceeded);
        } catch (UploadValidationException $exception) {
            $this->uploadService->failValidation($uploadEntity, $exception);

            return;
        }

        $this->uploadService->passValidation($uploadEntity, $mimeType);
    }

    private function loadUploadEntity(Uuid $uuid): UploadEntity
    {
        $uploadEntity = $this->repository->find($uuid);
        if (! $uploadEntity) {
            throw UploadException::forEntityNotFound($uuid);
        }

        if (! $uploadEntity->getStatus()->isUploaded()) {
            throw UploadException::forCannotDownload($uploadEntity);
        }

        return $uploadEntity;
    }

    private function validateMimetype(UploadEntity $uploadEntity): string
    {
        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeType(
            $uploadEntity->getFilename() ?? '',
            $this->workingCopyStorage->read($uploadEntity->getUploadId()),
        );

        if ($mimeType === null) {
            throw UploadValidationException::forCannotDetectMimetype($uploadEntity);
        }

        if (! $this->mimeTypeHelper->isValidForUploadGroup($mimeType, $uploadEntity->getUploadGroupId())) {
            throw UploadValidationException::forInvalidMimetype($uploadEntity, $mimeType);
        }

        return $mimeType;
    }

    private function scanFileContents(UploadEntity $uploadEntity, string $mimeType, bool $scanSizeExceeded): void
    {
        // Allow to skip too large zip files, the individual files within the archive are scanned later.
        if ($scanSizeExceeded && $this->isZipFile($uploadEntity, $mimeType)) {
            return;
        }

        $result = $this->clamAvFileScanner->scanResource(
            $uploadEntity->getUploadId(),
            $this->workingCopyStorage->readStream($uploadEntity->getUploadId()),
        );

        if ($result->isNotSafe()) {
            throw UploadValidationException::forUnsafeFile();
        }
    }

    private function isZipFile(UploadEntity $uploadEntity, string $mimeType): bool
    {
        $extension = pathinfo($uploadEntity->getFilename() ?? '', PATHINFO_EXTENSION);

        return $this->sevenZipFileStrategy->supports($extension, $mimeType);
    }
}
