<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Event;

use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final readonly class DossierTitleChangedEvent
{
    public function __construct(
        public Uuid $dossierId,
        public DossierTitle $oldDossierTitle,
        public DossierTitle $newDossierTitle,
    ) {
    }
}
