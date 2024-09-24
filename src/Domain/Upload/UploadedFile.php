<?php

declare(strict_types=1);

namespace App\Domain\Upload;

class UploadedFile extends \SplFileInfo
{
    public function __construct(string $filename, private ?string $originalFilename = null)
    {
        parent::__construct($filename);
    }

    public static function fromSplFile(\SplFileInfo $file, ?string $originalFilename = null): self
    {
        return new self($file->getPathname(), $originalFilename);
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
