<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierPropertiesViewFactory;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision as WooDecisionEntity;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Domain\Publication\MainDocument\ViewModel\MainDocumentViewFactory;
use App\Repository\WooDecisionRepository;
use Webmozart\Assert\Assert;

final readonly class WooDecisionViewFactory
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DepartmentViewFactory $departmentViewFactory,
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
        private MainDocumentViewFactory $mainDocumentViewFactory,
    ) {
    }

    public function make(WooDecisionEntity $dossier): WooDecision
    {
        $decisionDate = $dossier->getDecisionDate();
        Assert::notNull($decisionDate);

        $mainDocument = $dossier->getMainDocument();
        Assert::notNull($mainDocument);

        $decision = $dossier->getDecision();
        Assert::notNull($decision);

        $publicationReason = $dossier->getPublicationReason();
        Assert::notNull($publicationReason);

        $departments = $this->departmentViewFactory->makeCollection($dossier->getDepartments());

        return new WooDecision(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            counts: $this->wooDecisionRepository->getDossierCounts($dossier),
            departments: $departments,
            needsInventoryAndDocuments: $dossier->needsInventoryAndDocuments(),
            decision: $decision,
            decisionDate: $decisionDate,
            mainDocument: $this->mainDocumentViewFactory->make($dossier, $mainDocument),
            dateFrom: $dossier->getDateFrom(),
            dateTo: $dossier->getDateTo(),
            publicationReason: $publicationReason,
        );
    }
}
