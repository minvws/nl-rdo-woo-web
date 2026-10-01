<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer\DataProvider;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\InquiryInventory;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\Sanitizer\InventoryDocumentUrlStrategyInterface;
use Webmozart\Assert\Assert;

readonly class InquiryInventoryDataProvider implements InventoryDataProviderInterface
{
    public function __construct(
        private Inquiry $inquiry,
        /** @var array<array-key, Document> $documents */
        private array $documents,
        private InventoryDocumentUrlStrategyInterface $documentUrlStrategy,
    ) {
    }

    /**
     * @return array<array-key, Document>
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function getDossierForDocument(Document $document): WooDecision
    {
        $documentDossiers = $document->getDossiers();
        if ($documentDossiers->count() === 1) {
            $singleDossier = $documentDossiers->first();
            Assert::isInstanceOf($singleDossier, WooDecision::class);

            return $singleDossier;
        }

        $inquiryDossiersForDocument = $this->inquiry->getDossiers()->filter(
            static fn (WooDecision $candidateDossier): bool => $documentDossiers->contains($candidateDossier),
        );

        $publishedDossiersForDocument = $inquiryDossiersForDocument->filter(
            static fn (WooDecision $candidateDossier): bool => $candidateDossier->getStatus()->isPublished(),
        );

        $oldestPublishedDossier = null;
        $oldestPublishedDecisionDate = null;
        foreach ($publishedDossiersForDocument as $candidateDossier) {
            $candidateDecisionDate = $candidateDossier->getDecisionDate();
            Assert::notNull($candidateDecisionDate, 'A published WooDecision must have a decision date.');

            if ($oldestPublishedDossier === null) {
                $oldestPublishedDossier = $candidateDossier;
                $oldestPublishedDecisionDate = $candidateDecisionDate;
                continue;
            }

            Assert::notNull($oldestPublishedDecisionDate);
            if ($candidateDecisionDate->isBefore($oldestPublishedDecisionDate)) {
                $oldestPublishedDossier = $candidateDossier;
                $oldestPublishedDecisionDate = $candidateDecisionDate;
            }
        }

        $selectedDossier = $oldestPublishedDossier ?? $inquiryDossiersForDocument->first();

        Assert::isInstanceOf($selectedDossier, WooDecision::class);

        return $selectedDossier;
    }

    public function getDocumentUrlStrategy(): InventoryDocumentUrlStrategyInterface
    {
        return $this->documentUrlStrategy;
    }

    public function getInventoryEntity(): InquiryInventory
    {
        $inventory = $this->inquiry->getInventory();
        if (! $inventory) {
            $inventory = new InquiryInventory();
            $inventory->setInquiry($this->inquiry);
        }

        return $inventory;
    }

    public function getFilename(): string
    {
        return 'inventarislijst-' . $this->inquiry->getInquiryNumber();
    }
}
