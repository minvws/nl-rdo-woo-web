<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Disposition\ViewModel;

use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition as DispositionEntity;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
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
