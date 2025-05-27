<?php

declare(strict_types=1);

namespace App\Domain\Upload\Preprocessor\Strategy;

use App\Domain\Upload\AntiVirus\ClamAvFileScanner;
use App\Domain\Upload\Extractor\Extractor;
use App\Domain\Upload\Preprocessor\FilePreprocessorStrategyInterface;
use App\Domain\Upload\UploadedFile;
use Symfony\Component\Mime\MimeTypesInterface;

readonly class SevenZipFileStrategy implements FilePreprocessorStrategyInterface
{
    private const array SUPPORTED_EXTENSIONS = [
        '7z',
        'zip',
    ];

    private const array SUPPORTED_MIME_TYPES = [
        'application/zip',
        'application/x-7z-compressed',
    ];

    public function __construct(
        private Extractor $sevenZipExtractor,
        private MimeTypesInterface $mimeTypes,
        private ClamAvFileScanner $scanner,
    ) {
    }

    /**
     * @return \Generator<array-key,UploadedFile>
     */
    public function process(UploadedFile $file): \Generator
    {
        foreach ($this->sevenZipExtractor->getFiles($file) as $extractedFile) {
            if ($this->scanner->scan($extractedFile->getPathname())->isNotSafe()) {
                continue;
            }

            yield UploadedFile::fromFile($extractedFile);
        }
    }

    public function canProcess(UploadedFile $file): bool
    {
        return $this->supports(
            $file->getOriginalFileExtension(),
            $this->mimeTypes->guessMimeType($file->getPathname()) ?? '',
        );
    }

    public function supports(string $extension, string $mimetype): bool
    {
        return in_array($extension, self::SUPPORTED_EXTENSIONS, true)
            || in_array($mimetype, self::SUPPORTED_MIME_TYPES, true);
    }
}
