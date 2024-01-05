<?php

declare(strict_types=1);

namespace App\Service\Inventory;

use App\Entity\Dossier;

class DocumentNumber
{
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function getValue(): string
    {
        if (strlen($this->value) > 255) {
            return substr($this->value, 0, 255);
        }

        return $this->value;
    }

    public static function fromDossierAndDocumentMetadata(Dossier $dossier, DocumentMetadata $metadata): self
    {
        $documentNr = $dossier->getDocumentPrefix() . '-' . $metadata->getMatter() . '-' . $metadata->getId();

        return new self($documentNr);
    }
}
