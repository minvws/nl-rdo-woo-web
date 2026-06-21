<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\ValueObject\DossierTitle;

readonly class DossierReference
{
    private DossierType $type;

    public function __construct(
        private string $dossierNr,
        private string $documentPrefix,
        private DossierTitle $title,
        DossierType|string $type,
    ) {
        $this->type = $type instanceof DossierType ? $type : DossierType::from($type);
    }

    public static function fromEntity(AbstractDossier $dossier): self
    {
        return new self(
            $dossier->getDossierNr(),
            $dossier->getDocumentPrefix(),
            $dossier->getTitle(),
            $dossier->getType(),
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

    public function getTitle(): DossierTitle
    {
        return $this->title;
    }

    public function getType(): DossierType
    {
        return $this->type;
    }
}
