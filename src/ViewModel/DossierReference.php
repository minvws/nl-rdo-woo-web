<?php

declare(strict_types=1);

namespace App\ViewModel;

class DossierReference
{
    public function __construct(
        private readonly string $dossierNr,
        private readonly string $documentPrefix,
        private readonly string $title,
    ) {
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
