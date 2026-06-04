<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Event;

use Shared\Domain\Publication\Dossier\DossierStatus;
use Symfony\Component\Uid\Uuid;

final readonly class DossierNrChangedEvent
{
    public function __construct(
        public Uuid $dossierId,
        public string $oldDossierNr,
        public string $newDossierNr,
        public DossierStatus $status,
    ) {
    }
}
