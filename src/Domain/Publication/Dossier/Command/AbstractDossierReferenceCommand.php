<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Command;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

abstract readonly class AbstractDossierReferenceCommand
{
    final public function __construct(protected Uuid $uuid)
    {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forDossier(AbstractDossier $dossier): static
    {
        return new static($dossier->getId());
    }
}
