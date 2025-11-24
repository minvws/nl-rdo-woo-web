<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

readonly class Subject
{
    public function __construct(
        public string $name,
        public string $searchUrl,
    ) {
    }
}
