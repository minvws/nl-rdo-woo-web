<?php

declare(strict_types=1);

namespace App\Service\Storage;

use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

readonly class LocalFilesystem
{
    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @SuppressWarnings(ErrorControlOperator)
     *
     * @param resource $source
     * @param resource $target
     */
    public function copy($source, $target): bool
    {
        Assert::resource($source, 'stream');
        Assert::resource($target, 'stream');

        try {
            while (! feof($source)) {
                $data = fread($source, 1024 * 64);
                if ($data === false) {
                    break;
                }

                if (! fwrite($target, $data)) {
                    throw new StorageRuntimeException('Could not write data to target stream');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Could not copy data between streams', [
                'exception' => $e->getMessage(),
            ]);

            return false;
        } finally {
            @fclose($source);
            @fclose($target);
        }

        return true;
    }

    public function createTempFile(): string|false
    {
        $path = tempnam(sys_get_temp_dir(), 'woopie');
        if ($path === false) {
            $this->logger->error('Could not create temporary file', [
                'tempDir' => sys_get_temp_dir(),
            ]);

            return false;
        }

        return $path;
    }

    public function createTempDir(): string|false
    {
        $path = sys_get_temp_dir() . '/' . uniqid('woopie_', true);
        if (! mkdir($path)) {
            $this->logger->error('Could not create temporary dir', [
                'tempDir' => $path,
            ]);

            return false;
        }

        return $path;
    }

    public function deleteDirectory(string $dirPath): bool
    {
        if (! is_dir($dirPath)) {
            throw new StorageRuntimeException(sprintf('"%s" must be a directory', $dirPath));
        }

        // Add trailing slash to the path
        if (! str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }

        foreach ($this->streamSafeGlob($dirPath, '*') as $file) {
            $result = is_dir($file) ? $this->deleteDirectory($file) : unlink($file);
            if (! $result) {
                return false;
            }
        }

        if (! rmdir($dirPath)) {
            $this->logger->error('Could not delete directory', [
                'dirPath' => $dirPath,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings(ErrorControlOperator)
     */
    public function deleteFile(string $localPath): bool
    {
        if (! file_exists($localPath)) {
            return true;
        }

        $result = @unlink($localPath);
        if ($result === false) {
            $this->logger->error('Could not delete local file', [
                'local_path' => $localPath,
            ]);

            return false;
        }

        return true;
    }

    /**
     * @SuppressWarnings(ErrorControlOperator)
     *
     * @return resource|false
     */
    public function createStream(string $localPath, string $mode)
    {
        $stream = @fopen($localPath, $mode);
        if (! is_resource($stream)) {
            $this->logger->error('Could not open local file file', [
                'local_path' => $localPath,
                'mode' => $mode,
            ]);

            return false;
        }

        return $stream;
    }

    /**
     * Glob that is safe with streams (vfs for example).
     *
     * @return array<int,string>
     */
    protected function streamSafeGlob(string $directory, string $filePattern): array
    {
        $found = [];
        $files = scandir($directory);

        Assert::notFalse($files, 'Could not scan directory');

        foreach ($files as $filename) {
            if (in_array($filename, ['.', '..'])) {
                continue;
            }

            if (fnmatch($filePattern, $filename)) {
                $found[] = sprintf('%s/%s', $directory, $filename);
            }
        }

        return $found;
    }
}
