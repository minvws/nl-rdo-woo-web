<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Dossier;
use Symfony\Component\Uid\Uuid;

class IngestDecisionMessage
{
    public function __construct(
        private readonly Uuid $uuid,
        private readonly bool $refresh = false,
    ) {
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getRefresh(): bool
    {
        return $this->refresh;
    }

    public static function forDossier(Dossier $dossier): self
    {
        return new self($dossier->getId());
    }
}
