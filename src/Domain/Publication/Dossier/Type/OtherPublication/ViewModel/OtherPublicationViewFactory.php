<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\OtherPublication\ViewModel;

use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication as OtherPublicationEntity;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class OtherPublicationViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(OtherPublicationEntity $dossier): OtherPublication
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new OtherPublication(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
        );
    }
}
