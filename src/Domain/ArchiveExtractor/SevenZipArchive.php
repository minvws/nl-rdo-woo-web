<?php

declare(strict_types=1);

namespace App\Domain\ArchiveExtractor;

use App\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use App\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use App\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use App\Domain\ArchiveExtractor\Factory\SevenZipArchiveFactory;
use Archive7z\Archive7z;

final class SevenZipArchive implements ArchiveInterface
{
    private Archive7z $archive;

    public function __construct(private readonly SevenZipArchiveFactory $factory)
    {
    }

    public function open(\SplFileInfo $file): void
    {
        if (isset($this->archive)) {
            throw ArchiveLogicException::forArchiveIsAlreadyOpen($file);
        }

        $this->archive = $this->factory->create($file->getPathname(), timeout: 60.0 * 5);
    }

    public function close(): void
    {
        if (! isset($this->archive)) {
            throw ArchiveLogicException::forNoOpenArchive();
        }

        unset($this->archive);
    }

    public function extract(string $destination): void
    {
        if (! isset($this->archive)) {
            throw ArchiveLogicException::forNoOpenArchive();
        }

        try {
            $this->archive->setOutputDirectory($destination);
        } catch (\Exception $e) {
            throw ArchiveMissingDestinationException::create($destination);
        }

        try {
            $this->archive->extract();
        } catch (\Exception $e) {
            throw ArchiveRuntimeException::forExtractionFailure($e);
        }
    }
}
