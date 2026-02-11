<?php

declare(strict_types=1);

namespace Shared\Domain\Upload;

use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

use function pathinfo;

use const PATHINFO_EXTENSION;

class UploadedFile extends SplFileInfo
{
    public function __construct(string $filename, private readonly ?string $originalFilename = null)
    {
        parent::__construct($filename);
    }

    public static function fromFile(SplFileInfo|File $file, ?string $originalFilename = null): self
    {
        return new self($file->getPathname(), $originalFilename ?? $file->getBasename());
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename ?? $this->getFilename();
    }

    public function getOriginalFileExtension(): string
    {
        return pathinfo($this->getOriginalFilename(), PATHINFO_EXTENSION);
    }
}
