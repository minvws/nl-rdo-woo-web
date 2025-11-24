<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\FileType;

use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Log\LoggerInterface;
use Shared\Domain\Upload\UploadedFile;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\File\File;
use Webmozart\Assert\Assert;

readonly class MimeTypeHelper
{
    public const int SAMPLE_SIZE = 16 * 1024 * 1024;

    public function __construct(
        private FinfoMimeTypeDetector $mimeTypeDetector,
        private LoggerInterface $logger,
    ) {
    }

    public function isValidForUploadGroup(
        string $fileExtension,
        ?string $mimeType,
        UploadGroupId $groupId,
    ): bool {
        $groupExtensions = $groupId->getExtensions();
        if (! \in_array($fileExtension, $groupExtensions, true)) {
            $this->logger->warning('fileExtension not allowed in groupExtensions', [
                'fileExtension' => $fileExtension,
                'uploadGroupId' => $groupId->value,
            ]);

            return false;
        }

        $groupMimeTypes = $groupId->getMimeTypes();
        if ($mimeType === null || ! \in_array($mimeType, $groupMimeTypes, true)) {
            $this->logger->warning('mimeType not allowed in groupMimTypes', [
                'mimeType' => $mimeType,
                'uploadGroupId' => $groupId->value,
            ]);

            return false;
        }

        $fileExtensionsFromMimeType = FileType::fromMimeType($mimeType);
        Assert::isInstanceOf($fileExtensionsFromMimeType, FileType::class);

        if (! \in_array($fileExtension, $fileExtensionsFromMimeType->getExtensions())) {
            $this->logger->warning('fileExtension not allowed for fileExtensionsFromMimeType', [
                'fileExtension' => $fileExtension,
                'fileExtensionsFromMimeType' => \json_encode($fileExtensionsFromMimeType),
            ]);

            return false;
        }

        return true;
    }

    public function detectMimeType(string $path, string $contents): ?string
    {
        return $this->mimeTypeDetector->detectMimeType($path, $contents);
    }

    public function detectMimeTypeFromPath(File|UploadedFile $file): ?string
    {
        return $this->mimeTypeDetector->detectMimeTypeFromPath($this->getOriginalFileName($file));
    }

    private function getOriginalFileName(File|UploadedFile $file): string
    {
        return $file instanceof UploadedFile
            ? $file->getOriginalFilename()
            : $file->getFilename();
    }
}
