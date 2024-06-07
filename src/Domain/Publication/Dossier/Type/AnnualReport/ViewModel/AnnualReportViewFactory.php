<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport\ViewModel;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport as AnnualReportEntity;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Webmozart\Assert\Assert;

final readonly class AnnualReportViewFactory
{
    public function __construct(
        private DepartmentViewFactory $departmentViewFactory,
    ) {
    }

    public function make(AnnualReportEntity $dossier): AnnualReport
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        $mainDepartment = $dossier->getDepartments()->first();
        Assert::notFalse($mainDepartment);

        return new AnnualReport(
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
            year: $dateFrom->format('Y'),
        );
    }
}
