<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ViewModel;

final readonly class DossierType
{
    public function __construct(
        public string $type,
        public string $createUrl,
    ) {
    }
}
