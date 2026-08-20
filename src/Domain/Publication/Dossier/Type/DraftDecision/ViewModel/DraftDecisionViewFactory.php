<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\DraftDecision\ViewModel;

use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision as DraftDecisionEntity;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class DraftDecisionViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(DraftDecisionEntity $dossier): DraftDecision
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new DraftDecision(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
        );
    }
}
