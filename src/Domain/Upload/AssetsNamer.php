<?php

declare(strict_types=1);

namespace App\Domain\Upload;

use App\Domain\Department\Department;
use App\Domain\Publication\EntityWithFileInfo;
use App\Service\FilenameSanitizer;

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

    public function getDepartmentLogo(Department $department, string $extension): string
    {
        $fileName = sprintf('logo.%s', $this->sanitize($extension));

        return sprintf('%s%s', $this->getStorageSubpath($department), $fileName);
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
