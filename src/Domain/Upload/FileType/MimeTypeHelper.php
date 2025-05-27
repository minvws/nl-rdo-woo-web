<?php

declare(strict_types=1);

namespace App\Domain\Upload\FileType;

use App\Domain\Upload\UploadedFile;
use App\Service\Uploader\UploadGroupId;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Symfony\Component\HttpFoundation\File\File;

readonly class MimeTypeHelper
{
    public const int SAMPLE_SIZE = 16 * 1024 * 1024;

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

    public function detectMimeType(FileInterface|File|UploadedFile|FilesystemFile $file): ?string
    {
        $detector = new FinfoMimeTypeDetector();

        $startOfFileContent = file_get_contents($file->getPathname(), length: self::SAMPLE_SIZE);
        if (! $startOfFileContent) {
            return null;
        }

        return $detector->detectMimeType(
            $this->getOriginalFileName($file),
            $startOfFileContent,
        );
    }

    private function getOriginalFileName(FileInterface|File|UploadedFile|FilesystemFile $file): string
    {
        if ($file instanceof FilesystemFile) {
            return $file->getClientOriginalName();
        }

        if ($file instanceof UploadedFile) {
            return $file->getOriginalFilename();
        }

        return '';
    }
}
