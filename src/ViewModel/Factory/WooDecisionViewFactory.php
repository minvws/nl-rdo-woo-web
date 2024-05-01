<?php

declare(strict_types=1);

namespace App\ViewModel\Factory;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision as WooDecisionEntity;
use App\Repository\WooDecisionRepository;
use App\ViewModel\WooDecision;
use Webmozart\Assert\Assert;

final readonly class WooDecisionViewFactory
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function make(WooDecisionEntity $dossier): WooDecision
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        $mainDepartment = $dossier->getDepartments()->first();
        Assert::notFalse($mainDepartment);

        $decisionDate = $dossier->getDecisionDate();
        Assert::notNull($decisionDate);

        $decisionDocument = $dossier->getDecisionDocument();
        Assert::notNull($decisionDocument);

        $publicationReason = $dossier->getPublicationReason();
        Assert::notNull($publicationReason);

        return new WooDecision(
            entity: $dossier,
            counts: $this->wooDecisionRepository->getDossierCounts($dossier),
            dossierId: $dossier->getId()->toRfc4122(),
            dossierNr: $dossier->getDossierNr(),
            documentPrefix: $dossier->getDocumentPrefix(),
            isPreview: $dossier->getStatus()->isPreview(),
            title: $title,
            pageTitle: $dossier->getStatus()->isPreview()
                ? sprintf('%s %s', $title, '(preview)')
                : $title,
            publicationDate: $publicationDate,
            mainDepartment: $mainDepartment, // TODO should be a ViewModel Department
            summary: $dossier->getSummary(),
            needsInventoryAndDocuments: $dossier->needsInventoryAndDocuments(),
            decision: $dossier->getDecision(),
            decisionDate: $decisionDate,
            inventory: $dossier->getInventory(), // TODO should be a ViewModel Inventory or more generic PublicationItem
            decisionDocument: $decisionDocument, // TODO should be a ViewModel DecisionDocument or more generic PublicationItem
            dateFrom: $dossier->getDateFrom(),
            dateTo: $dossier->getDateTo(),
            publicationReason: $publicationReason,
        );
    }
}
