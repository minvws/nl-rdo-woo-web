<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\RequestForAdvice\ViewModel;

use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice as RequestForAdviceEntity;
use App\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class RequestForAdviceViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(RequestForAdviceEntity $dossier): RequestForAdvice
    {
        $dateFrom = $dossier->getDateFrom();
        Assert::notNull($dateFrom);

        return new RequestForAdvice(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            date: $dateFrom,
            link: $dossier->getLink(),
            advisoryBodies: $dossier->getAdvisoryBodies(),
        );
    }
}
