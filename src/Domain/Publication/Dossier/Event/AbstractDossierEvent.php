<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Event;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractDossierEvent
{
    final private function __construct(
        public Uuid $id,
    ) {
    }

    public static function forDossier(AbstractDossier $dossier): static
    {
        return new static($dossier->getId());
    }
}
