<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\Extractor;

use Generator;
use Shared\Domain\ArchiveExtractor\ArchiveInterface;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveExceptionInterface;
use Shared\Domain\ArchiveExtractor\Exception\ArchiveRuntimeException;
use Shared\Service\Storage\LocalFilesystem;
use SplFileInfo;

readonly class Extractor
{
    public function __construct(
        private ArchiveInterface $archive,
        private LocalFilesystem $filesystem,
        private ExtractorFinderFactory $finderFactory,
    ) {
    }

    /**
     * Note that after iterating over the files once, the temporary directory with the extracted files will be deleted.
     * You need to process the files as you iterate over them. For example don't call iterator_to_array on the result.
     *
     * @return Generator<array-key,SplFileInfo>
     */
    public function getFiles(SplFileInfo $file): Generator
    {
        try {
            $this->archive->open($file);
        } catch (ArchiveRuntimeException $e) {
            throw ExtractorException::forFailingToOpenArchive($file, $e);
        }

        try {
            $tempDir = $this->filesystem->createTempDir();
            if ($tempDir === false) {
                throw ExtractorException::forFailingToCreateTempDir($file);
            }

            $this->archive->extract($tempDir);

            yield from $this->finderFactory->create($tempDir)->getIterator();
        } catch (ArchiveExceptionInterface $e) {
            throw ExtractorException::forFailingToExtractFiles($file, $tempDir, $e);
        } finally {
            $this->archive->close();

            if (isset($tempDir) && $tempDir !== false) {
                $this->filesystem->deleteDirectory($tempDir);
            }
        }
    }
}
