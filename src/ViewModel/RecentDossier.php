<?php

declare(strict_types=1);

namespace App\ViewModel;

class RecentDossier
{
    public function __construct(
        private readonly string $dossierNr,
        private readonly string $prefix,
        private readonly string $title,
        private readonly \DateTimeImmutable $publicationDate,
        private readonly \DateTimeImmutable $decisionDate,
        private readonly int $documentCount,
        private readonly int $pageCount,
    ) {
    }

    public function getDossierNr(): string
    {
        return $this->dossierNr;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPublicationDate(): \DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function getDecisionDate(): \DateTimeImmutable
    {
        return $this->decisionDate;
    }

    public function getDocumentCount(): int
    {
        return $this->documentCount;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }
}
