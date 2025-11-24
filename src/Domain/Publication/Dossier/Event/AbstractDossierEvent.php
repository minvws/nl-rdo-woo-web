<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Event;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractDossierEvent
{
    final private function __construct(
        public Uuid $dossierId,
    ) {
    }

    public static function forDossier(AbstractDossier $dossier): static
    {
        return new static($dossier->getId());
    }
}
