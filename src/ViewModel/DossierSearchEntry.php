<?php

declare(strict_types=1);

namespace App\ViewModel;

class DossierSearchEntry
{
    public function __construct(
        private readonly string $dossierNr,
        private readonly string $documentPrefix,
        private readonly string $title,
        private readonly string $decision,
        private readonly string $summary,
        private readonly \DateTimeImmutable $publicationDate,
        private readonly \DateTimeImmutable $decisionDate,
        private readonly int $documentCount,
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

    public function getPublicationDate(): \DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function getDocumentCount(): int
    {
        return $this->documentCount;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDecisionDate(): \DateTimeImmutable
    {
        return $this->decisionDate;
    }
}
