<?php

declare(strict_types=1);

namespace App\Message;

use App\Entity\Document;
use App\Entity\Dossier;
use Symfony\Component\Uid\Uuid;

class RemoveDocumentMessage
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

    public static function forDossierAndDocument(Dossier $dossier, Document $document): self
    {
        return new self($dossier->getId(), $document->getId());
    }
}
