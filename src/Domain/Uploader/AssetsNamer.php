<?php

declare(strict_types=1);

namespace App\Domain\Uploader;

use App\Domain\Publication\EntityWithFileInfo;
use App\Entity\Department;
use App\Service\FilenameSanitizer;
use Symfony\Component\Uid\Uuid;

readonly class AssetsNamer
{
    public function getStorageSubpath(EntityWithFileInfo $entityWithFile): string
    {
        $subDir = match (true) {
            $entityWithFile instanceof Department => 'department',
            default => 'misc',
        };

        return sprintf('%s/%s/', $subDir, $entityWithFile->getId()->toRfc4122());
    }

    public function getDepartmentLogo(Department $department, \SplFileInfo $file): string
    {
        $fileName = sprintf('%s.%s', $this->getUuid(), $this->sanitize($file->getExtension()));

        return sprintf('%s%s', $this->getStorageSubpath($department), $fileName);
    }

    protected function getUuid(): Uuid
    {
        return Uuid::v6();
    }

    private function sanitize(string $filename): string
    {
        $sanitizer = new FilenameSanitizer($filename);
        $sanitizer->stripAdditionalCharacters();
        $sanitizer->stripIllegalFilesystemCharacters();
        $sanitizer->stripRiskyCharacters();

        return $sanitizer->getFilename();
    }
}
