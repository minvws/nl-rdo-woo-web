<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Command;

use League\Flysystem\FilesystemOperator;
use Shared\Domain\Upload\AntiVirus\ClamAvFileScanner;
use Shared\Domain\Upload\Exception\UploadException;
use Shared\Domain\Upload\Exception\UploadValidationException;
use Shared\Domain\Upload\FileType\FileType;
use Shared\Domain\Upload\FileType\MimeTypeHelper;
use Shared\Domain\Upload\Preprocessor\Strategy\SevenZipFileStrategy;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
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

            $this->validateUploadSize($uploadEntity, $mimeType);

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
        $mimeType = $this->mimeTypeHelper->detectMimeType(
            $uploadEntity->getFilename() ?? '',
            $this->workingCopyStorage->read($uploadEntity->getUploadId()),
        );

        if ($mimeType === null) {
            throw UploadValidationException::forCannotDetectMimetype($uploadEntity);
        }

        Assert::string($uploadEntity->getFilename());
        $fileExtension = pathinfo($uploadEntity->getFilename(), PATHINFO_EXTENSION);

        if (! $this->mimeTypeHelper->isValidForUploadGroup($fileExtension, $mimeType, $uploadEntity->getUploadGroupId())) {
            throw UploadValidationException::forInvalidMimetype($uploadEntity, $mimeType);
        }

        return $mimeType;
    }

    private function validateUploadSize(UploadEntity $uploadEntity, string $mimeType): void
    {
        $fileType = FileType::fromMimeType($mimeType);
        Assert::isInstanceOf($fileType, FileType::class);

        $size = $uploadEntity->getSize();
        Assert::notNull($size);

        if ($size > $fileType->getMaxUploadSize()) {
            throw UploadValidationException::forFilesizeExceeded($uploadEntity, $fileType);
        }
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
