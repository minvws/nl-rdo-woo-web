<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport\ViewModel;

use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport as InvestigationReportEntity;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class InvestigationReportViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(InvestigationReportEntity $dossier): InvestigationReport
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new InvestigationReport(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
        );
    }
}
