<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

class FileInfo
{
    public function __construct(
        private readonly string $name,
        private readonly string $sourceType,
        private readonly bool $uploaded,
        private readonly int $size,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function isUploaded(): bool
    {
        return $this->uploaded;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
