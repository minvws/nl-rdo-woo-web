<?php

declare(strict_types=1);

namespace Shared\Service\Inventory\Sanitizer;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentUrlGenerator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Webmozart\Assert\Assert;

readonly class InquiryInventoryDocumentUrlStrategy implements InventoryDocumentUrlStrategyInterface
{
    public function __construct(
        private DocumentUrlGenerator $documentUrlGenerator,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function generateDocumentUrl(Document $document, WooDecision $inventoryDossier): string
    {
        $documentDossiers = $document->getDossiers();
        Assert::greaterThan(
            $documentDossiers->count(),
            0,
            'An inquiry inventory document must belong to at least one dossier.',
        );

        if ($documentDossiers->count() > 1) {
            return $this->documentUrlGenerator->canonicalUrl($document->getDocumentNumber())->toString();
        }

        $documentDossier = $documentDossiers->first();
        Assert::isInstanceOf($documentDossier, WooDecision::class);

        return $this->documentUrlGenerator->detailUrl($document, $documentDossier)->toString();
    }

    public function generateRelatedDocumentUrl(Document $document, WooDecision $inventoryDossier): string
    {
        if (
            ! $this->wooDecisionRepository->hasPubliclyAvailableDossierForDocument(
                $document->getDocumentNumber(),
            )
        ) {
            return '';
        }

        $documentDossiers = $document->getDossiers();
        if ($documentDossiers->count() > 1) {
            return $this->documentUrlGenerator->canonicalUrl($document->getDocumentNumber())->toString();
        }

        $documentDossier = $documentDossiers->first();
        Assert::isInstanceOf($documentDossier, WooDecision::class);

        return $this->documentUrlGenerator->detailUrl($document, $documentDossier)->toString();
    }
}
