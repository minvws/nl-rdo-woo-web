<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\ViewModel;

final readonly class DossierType
{
    public function __construct(
        public string $type,
        public string $createUrl,
    ) {
    }
}
