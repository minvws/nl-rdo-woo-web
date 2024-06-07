<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

final readonly class PublicationItem
{
    public function __construct(
        public string $fileName,
        public int $fileSize,
        public bool $isUploaded,
    ) {
    }
}
