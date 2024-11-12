<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\ViewModel;

readonly class Page
{
    public function __construct(
        public int $pageNr,
        public ?string $thumbnailUrl,
        public ?string $viewUrl,
    ) {
    }

    public function hasThumbnail(): bool
    {
        return $this->thumbnailUrl !== null;
    }
}
