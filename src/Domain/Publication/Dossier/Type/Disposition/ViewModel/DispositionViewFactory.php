<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition\ViewModel;

use App\Domain\Publication\Dossier\Type\Disposition\Disposition as DispositionEntity;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class DispositionViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(DispositionEntity $dossier): Disposition
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new Disposition(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
        );
    }
}
