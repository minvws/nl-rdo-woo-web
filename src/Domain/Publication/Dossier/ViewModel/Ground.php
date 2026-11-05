<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

readonly class Ground
{
    public function __construct(
        public string $citation,
        public string $classification,
        public ?string $url,
    ) {
    }
}
