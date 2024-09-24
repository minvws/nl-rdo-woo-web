<?php

declare(strict_types=1);

namespace App\Domain\ArchiveExtractor;

use App\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use App\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use App\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use App\Domain\ArchiveExtractor\Factory\ZipArchiveFactory;

final class ZipArchive implements ArchiveInterface
{
    private \ZipArchive $archive;

    public function __construct(private readonly ZipArchiveFactory $factory)
    {
    }

    public function open(\SplFileInfo $file): void
    {
        if (isset($this->archive)) {
            throw ArchiveLogicException::forArchiveIsAlreadyOpen($file);
        }

        $this->archive = $this->factory->create();

        // \ZipArchive::open can return true, false or an int error code
        if ($this->archive->open($file->getPathname()) !== true) {
            throw ArchiveRuntimeException::forFailedToOpenArchive($file);
        }
    }

    public function close(): void
    {
        if (! isset($this->archive)) {
            throw ArchiveLogicException::forNoOpenArchive();
        }

        if (! $this->archive->close()) {
            throw ArchiveRuntimeException::forFailedToCloseArchive();
        }

        unset($this->archive);
    }

    public function extract(string $destination): void
    {
        if (! isset($this->archive)) {
            throw ArchiveLogicException::forNoOpenArchive();
        }

        if (! is_dir($destination) || ! is_writable($destination)) {
            throw ArchiveMissingDestinationException::create($destination);
        }

        if (! $this->archive->extractTo($destination)) {
            throw ArchiveRuntimeException::forExtractionFailure();
        }
    }
}
