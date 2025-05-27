<?php

declare(strict_types=1);

namespace App\Domain\Publication\BatchDownload\Archiver;

use App\Domain\Publication\BatchDownload\BatchDownload;
use App\Service\FilenameSanitizer;

readonly class ArchiveNamer
{
    public function getArchiveName(string $basename, BatchDownload $batchDownload): string
    {
        $filename = sprintf(
            '%s-%s.zip',
            $basename,
            $batchDownload->getId()->toRfc4122(),
        );

        return $this->sanitize($filename);
    }

    public function getArchiveNameForStream(string $basename): string
    {
        $filename = sprintf('%s.zip', $basename);

        return $this->sanitize($filename);
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
