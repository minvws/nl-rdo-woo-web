<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;

readonly class DossierInventoryDocumentUrlStrategy implements InventoryDocumentUrlStrategyInterface
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentUrlGenerator $documentUrlGenerator,
    ) {
    }

    public function generateDocumentUrl(Document $document, WooDecision $inventoryDossier): string
    {
        return $this->documentUrlGenerator->detailUrl($document, $inventoryDossier)->toString();
    }

    public function generateRelatedDocumentUrl(Document $document, WooDecision $inventoryDossier): string
    {
        if ($document->getDossiers()->contains($inventoryDossier)) {
            return $this->documentUrlGenerator->detailUrl($document, $inventoryDossier)->toString();
        }

        if (
            ! $this->wooDecisionRepository->hasPublishedDossierForDocumentExcept(
                $document->getDocumentNumber(),
                $inventoryDossier,
            )
        ) {
            return '';
        }

        return $this->documentUrlGenerator->canonicalUrl($document->getDocumentNumber())->toString();
    }
}
