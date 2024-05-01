<?php

declare(strict_types=1);

namespace App\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

final readonly class IndexDossierMessage
{
    public function __construct(
        private Uuid $uuid,
        private bool $refresh = true,
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

    public static function forDossier(AbstractDossier $dossier): static
    {
        return new static($dossier->getId());
    }
}
