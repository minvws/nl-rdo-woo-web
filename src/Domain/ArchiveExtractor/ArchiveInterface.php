<?php

declare(strict_types=1);

namespace App\Domain\ArchiveExtractor;

use App\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use App\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use App\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;

interface ArchiveInterface
{
    /**
     * @throws ArchiveLogicException   When called with an already open archive
     * @throws ArchiveRuntimeException When the archive cannot be opened
     */
    public function open(\SplFileInfo $file): void;

    /**
     * @throws ArchiveLogicException   When called without an open archive
     * @throws ArchiveRuntimeException When the archive cannot be closed
     */
    public function close(): void;

    /**
     * @throws ArchiveLogicException              When called without an open archive
     * @throws ArchiveMissingDestinationException When the destination is missing or not writable
     * @throws ArchiveLogicException              When the extraction fails
     */
    public function extract(string $destination): void;
}
