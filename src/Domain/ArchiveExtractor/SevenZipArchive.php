<?php

declare(strict_types=1);

namespace Shared\Domain\ArchiveExtractor;

use Archive7z\Archive7z;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use Shared\Domain\ArchiveExtractor\Factory\SevenZipArchiveFactory;

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
