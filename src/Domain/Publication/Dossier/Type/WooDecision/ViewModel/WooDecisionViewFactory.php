<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision as WooDecisionEntity;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use App\Domain\Publication\Dossier\ViewModel\PublicationItemViewFactory;
use App\Repository\WooDecisionRepository;
use Webmozart\Assert\Assert;

final readonly class WooDecisionViewFactory
{
    public function __construct(
        private WooDecisionRepository $wooDecisionRepository,
        private DepartmentViewFactory $departmentViewFactory,
        private PublicationItemViewFactory $publicationItemViewFactory,
    ) {
    }

    public function make(WooDecisionEntity $dossier): WooDecision
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        $decisionDate = $dossier->getDecisionDate();
        Assert::notNull($decisionDate);

        $decisionDocument = $dossier->getDecisionDocument();
        Assert::notNull($decisionDocument);

        $decision = $dossier->getDecision();
        Assert::notNull($decision);

        $publicationReason = $dossier->getPublicationReason();
        Assert::notNull($publicationReason);

        $departments = $this->departmentViewFactory->makeCollection($dossier->getDepartments());
        $mainDepartment = $departments->first();
        Assert::notFalse($mainDepartment);

        return new WooDecision(
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
            mainDepartment: $mainDepartment,
            departments: $departments,
            summary: $dossier->getSummary(),
            needsInventoryAndDocuments: $dossier->needsInventoryAndDocuments(),
            decision: $decision,
            decisionDate: $decisionDate,
            decisionDocument: $this->publicationItemViewFactory->make($decisionDocument),
            dateFrom: $dossier->getDateFrom(),
            dateTo: $dossier->getDateTo(),
            publicationReason: $publicationReason,
            subject: $dossier->getSubject()?->getName(),
        );
    }
}
