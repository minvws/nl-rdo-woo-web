<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

readonly class DossierFile
{
    /**
     * @var array<array-key, Page>
     */
    public array $pages;

    public function __construct(
        public string $type,
        public int $size,
        public bool $hasPages,
        public string $downloadUrl,
        Page ...$pages,
    ) {
        $this->pages = $pages;
    }
}
