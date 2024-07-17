<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;

readonly class DossierReference
{
    public function __construct(
        private string $dossierNr,
        private string $documentPrefix,
        private string $title,
    ) {
    }

    public static function fromEntity(AbstractDossier $dossier): self
    {
        return new self(
            $dossier->getDossierNr(),
            $dossier->getDocumentPrefix(),
            $dossier->getTitle() ?? '',
        );
    }

    public function getDossierNr(): string
    {
        return $this->dossierNr;
    }

    public function getDocumentPrefix(): string
    {
        return $this->documentPrefix;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
