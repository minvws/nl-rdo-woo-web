<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Dossier;
use Symfony\Component\Uid\Uuid;

abstract class AbstractDossierMessage
{
    protected Uuid $uuid;

    final public function __construct(Uuid $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public static function forDossier(Dossier $dossier): static
    {
        return new static($dossier->getId());
    }
}
