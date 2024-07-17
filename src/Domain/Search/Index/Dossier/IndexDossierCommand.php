<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

final readonly class IndexDossierCommand
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

    public static function forDossier(AbstractDossier $dossier, bool $refresh = true): self
    {
        return new self($dossier->getId(), $refresh);
    }
}
