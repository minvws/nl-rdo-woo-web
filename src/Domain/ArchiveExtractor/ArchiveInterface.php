<?php

declare(strict_types=1);

namespace Shared\Domain\ArchiveExtractor;

use Shared\Domain\ArchiveExtractor\Exception\ArchiveLogicException;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveMissingDestinationException;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;

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
