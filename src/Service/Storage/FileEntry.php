<?php

declare(strict_types=1);

namespace App\Service\Storage;

class FileEntry
{
    public const TYPE_FILE = 'file';
    public const TYPE_DIRECTORY = 'dir';

    protected string $path;
    protected string $type;
    protected string $visibility;
    protected int $lastModified;
    protected int $fileSize;

    public function __construct(string $path, string $type, string $visibility, int $lastModified, int $fileSize)
    {
        $this->path = $path;
        $this->type = $type;
        $this->visibility = $visibility;
        $this->lastModified = $lastModified;
        $this->fileSize = $fileSize;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function getLastModified(): int
    {
        return $this->lastModified;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function isDir(): bool
    {
        return $this->type === self::TYPE_DIRECTORY;
    }
}
