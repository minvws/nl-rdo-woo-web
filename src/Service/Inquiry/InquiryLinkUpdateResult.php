<?php

declare(strict_types=1);

namespace App\Service\Inquiry;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Symfony\Component\Uid\Uuid;

class InquiryLinkUpdateResult
{
    private int $addedDossierCount = 0;
    private bool $needsFileUpdate = false;

    /**
     * @var array<array-key,Uuid>
     */
    private array $updatedDossierIds = [];

    /**
     * @var array<array-key,Uuid>
     */
    private array $updatedDocumentIds = [];

    public function __construct(
        private readonly Inquiry $inquiry,
        private readonly string $caseNr,
    ) {
    }

    public function dossierAdded(WooDecision $dossier): void
    {
        $this->addedDossierCount++;
        $this->updatedDossierIds[] = $dossier->getId();
        $this->needsFileUpdate = true;
    }

    public function documentAdded(Document $document): void
    {
        $this->updatedDocumentIds[] = $document->getId();
    }

    public function documentRemoved(Document $document): void
    {
        $this->updatedDocumentIds[] = $document->getId();
        $this->needsFileUpdate = true;
    }

    public function hasAddedDossiers(): bool
    {
        return $this->addedDossierCount > 0;
    }

    public function needsFileUpdate(): bool
    {
        return $this->needsFileUpdate;
    }

    /**
     * @return array<array-key,Uuid>
     */
    public function getUpdatedDossierIds(): array
    {
        return $this->updatedDossierIds;
    }

    /**
     * @return array<array-key,Uuid>
     */
    public function getUpdatedDocumentIds(): array
    {
        return $this->updatedDocumentIds;
    }

    public function getAddedDossierCount(): int
    {
        return $this->addedDossierCount;
    }

    public function getInquiry(): Inquiry
    {
        return $this->inquiry;
    }

    public function getCaseNr(): string
    {
        return $this->caseNr;
    }
}
