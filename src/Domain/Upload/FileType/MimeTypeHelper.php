<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use App\Domain\Upload\UploadedFile;
use App\Service\Uploader\UploadGroupId;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Symfony\Component\HttpFoundation\File\File;

readonly class MimeTypeHelper
{
    public const int SAMPLE_SIZE = 16 * 1024 * 1024;

    public function __construct(
        private FinfoMimeTypeDetector $mimeTypeDetector,
    ) {
    }

    public function isValidForUploadGroup(
        ?string $mimeType,
        UploadGroupId $groupId,
    ): bool {
        if ($mimeType === null) {
            return false;
        }

        return in_array(
            $mimeType,
            $groupId->getMimeTypes(),
            true,
        );
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
