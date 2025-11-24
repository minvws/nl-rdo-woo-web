<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Advice\ViewModel;

use Shared\Domain\Publication\Dossier\Type\Advice\Advice as AdviceEntity;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class AdviceViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(AdviceEntity $dossier): Advice
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new Advice(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
        );
    }
}
