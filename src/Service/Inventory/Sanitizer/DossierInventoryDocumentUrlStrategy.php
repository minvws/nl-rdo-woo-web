<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentCanonicalUrlGenerator;
use Shared\Service\DocumentDossierUrlGenerator;

readonly class DossierInventoryDocumentUrlStrategy implements InventoryDocumentUrlStrategyInterface
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentDossierUrlGenerator $documentDossierUrlGenerator,
        private DocumentCanonicalUrlGenerator $documentCanonicalUrlGenerator,
    ) {
    }

    public function generateDocumentUrl(Document $document, WooDecision $inventoryDossier): string
    {
        return $this->documentDossierUrlGenerator->dossier($document, $inventoryDossier);
    }

    public function generateRelatedDocumentUrl(Document $document, WooDecision $inventoryDossier): string
    {
        if ($document->getDossiers()->contains($inventoryDossier)) {
            return $this->documentDossierUrlGenerator->dossier($document, $inventoryDossier);
        }

        if (
            ! $this->wooDecisionRepository->hasPublishedDossierForDocumentExcept(
                $document->getDocumentNumber(),
                $inventoryDossier,
            )
        ) {
            return '';
        }

        return $this->documentCanonicalUrlGenerator->canonical($document->getDocumentNumber());
    }
}
