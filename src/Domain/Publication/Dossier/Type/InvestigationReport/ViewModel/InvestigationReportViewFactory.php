<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport as InvestigationReportEntity;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Webmozart\Assert\Assert;

final readonly class InvestigationReportViewFactory
{
    public function __construct(
        private DepartmentViewFactory $departmentViewFactory,
    ) {
    }

    public function make(InvestigationReportEntity $dossier): InvestigationReport
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        $mainDepartment = $dossier->getDepartments()->first();
        Assert::notFalse($mainDepartment);

        return new InvestigationReport(
            dossierId: $dossier->getId()->toRfc4122(),
            dossierNr: $dossier->getDossierNr(),
            documentPrefix: $dossier->getDocumentPrefix(),
            isPreview: $dossier->getStatus()->isPreview(),
            title: $title,
            pageTitle: $dossier->getStatus()->isPreview()
                ? sprintf('%s %s', $title, '(preview)')
                : $title,
            publicationDate: $publicationDate,
            mainDepartment: $this->departmentViewFactory->make($mainDepartment),
            summary: $dossier->getSummary(),
            type: $dossier->getType(),
            date: $dateFrom,
        );
    }
}
