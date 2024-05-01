<?php

declare(strict_types=1);

namespace App\Service\Uploader;

use Oneup\UploaderBundle\Uploader\Chunk\Storage\FilesystemStorage as OneupFilesystemStorage;
use Oneup\UploaderBundle\Uploader\File\FilesystemFile;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This was mainly needed because vfsStream cannot handle ->getRealPath(). It would always return false. In this class
 * it will fall back to ->getpathname().
 */
final class FilesystemStorage extends OneupFilesystemStorage
{
    /**
     * @param \IteratorAggregate $chunks
     */
    public function assembleChunks($chunks, bool $removeChunk, bool $renameChunk): File
    {
        if (! ($chunks instanceof \IteratorAggregate)) {
            throw new \InvalidArgumentException('The first argument must implement \IteratorAggregate interface.');
        }

        /** @var \Iterator<array-key,\SplFileInfo> $iterator */
        $iterator = $chunks->getIterator();

        $base = $iterator->current();
        $iterator->next();

        while ($iterator->valid()) {
            $file = $iterator->current();

            if (file_put_contents($base->getPathname(), file_get_contents($file->getPathname()), \FILE_APPEND | \LOCK_EX) === false) {
                throw new \RuntimeException('Reassembling chunks failed.');
            }

            if ($removeChunk) {
                $filesystem = new Filesystem();
                $filesystem->remove($file->getPathname());
            }

            $iterator->next();
        }

        $name = $base->getBasename();

        if ($renameChunk) {
            // remove the prefix added by self::addChunk
            $name = preg_replace('/^(\d+)_/', '', $base->getBasename());
        }

        $assembledPath = ($realPath = $base->getRealPath()) !== false
            ? $realPath
            : $base->getPathName();

        $assembled = new File($assembledPath);
        $assembled = $assembled->move($base->getPath(), $name);

        // the file is only renamed before it is uploaded
        if ($renameChunk) {
            // create an file to meet interface restrictions
            $file = new UploadedFile($assembled->getPathname(), $assembled->getBasename(), null, null, true);
            $assembled = new FilesystemFile($file);
        }

        return $assembled;
    }
}
