<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ViewModel;

readonly class Subject
{
    public function __construct(
        public string $name,
        public string $searchUrl,
    ) {
    }
}
