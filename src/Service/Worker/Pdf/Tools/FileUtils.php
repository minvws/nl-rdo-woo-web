<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Tools;

class FileUtils
{
    public function createTempDir(): string
    {
        // create temp dir
        $tempDir = sys_get_temp_dir() . '/' . uniqid('woopie_', true);
        mkdir($tempDir);

        return $tempDir;
    }

    public function deleteTempDirectory(string $dirPath): void
    {
        $this->deleteDirectory($dirPath);
    }

    protected function deleteDirectory(string $dirPath): void
    {
        if (! is_dir($dirPath)) {
            throw new \InvalidArgumentException("$dirPath must be a directory");
        }

        // Add trailing slash to the path
        if (! str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);
        foreach (is_iterable($files) ? $files : [] as $file) {
            if (is_dir($file)) {
                $this->deleteDirectory($file);
            } else {
                unlink($file);
            }
        }

        rmdir($dirPath);
    }
}
