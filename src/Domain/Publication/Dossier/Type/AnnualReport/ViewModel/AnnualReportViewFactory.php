<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport\ViewModel;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport as AnnualReportEntity;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class AnnualReportViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(AnnualReportEntity $dossier): AnnualReport
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new AnnualReport(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            year: $dateFrom->format('Y'),
        );
    }
}
