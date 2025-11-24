<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel;

use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement as ComplaintJudgementEntity;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class ComplaintJudgementViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(ComplaintJudgementEntity $dossier): ComplaintJudgement
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new ComplaintJudgement(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
        );
    }
}
