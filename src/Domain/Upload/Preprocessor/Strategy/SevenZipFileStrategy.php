<?php

declare(strict_types=1);

namespace App\Domain\Upload\Preprocessor\Strategy;

use App\Domain\Upload\Extractor\Extractor;
use App\Domain\Upload\Preprocessor\FilePreprocessorStrategyInterface;
use App\Domain\Upload\UploadedFile;

final readonly class SevenZipFileStrategy implements FilePreprocessorStrategyInterface
{
    public function __construct(private Extractor $sevenZipExtractor)
    {
    }

    /**
     * @return \Generator<array-key,UploadedFile>
     */
    public function process(UploadedFile $file): \Generator
    {
        foreach ($this->sevenZipExtractor->getFiles($file) as $extractedFile) {
            yield UploadedFile::fromSplFile($extractedFile);
        }
    }

    public function canProcess(UploadedFile $file): bool
    {
        return $file->getOriginalFileExtension() === '7z';
    }
}
