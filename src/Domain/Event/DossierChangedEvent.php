<?php

declare(strict_types=1);

namespace Shared\Domain\Event;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Contracts\EventDispatcher\Event;

class DossierChangedEvent extends Event
{
    public function __construct(
        private readonly AbstractDossier $dossier,
    ) {
    }

    public function getDossier(): AbstractDossier
    {
        return $this->dossier;
    }
}
