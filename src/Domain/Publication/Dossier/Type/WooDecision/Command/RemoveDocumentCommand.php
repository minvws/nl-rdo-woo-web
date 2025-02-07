<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\Command;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use Symfony\Component\Uid\Uuid;

class RemoveDocumentCommand
{
    public function __construct(
        private readonly Uuid $dossierId,
        private readonly Uuid $documentId,
    ) {
    }

    public function getDossierId(): Uuid
    {
        return $this->dossierId;
    }

    public function getDocumentId(): Uuid
    {
        return $this->documentId;
    }

    public static function forDossierAndDocument(WooDecision $dossier, Document $document): self
    {
        return new self($dossier->getId(), $document->getId());
    }
}
