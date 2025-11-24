<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant as CovenantEntity;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Webmozart\Assert\Assert;

final readonly class CovenantViewFactory
{
    public function __construct(
        private CommonDossierPropertiesViewFactory $commonDossierViewFactory,
    ) {
    }

    public function make(CovenantEntity $dossier): Covenant
    {
        $parties = $dossier->getParties();
        Assert::isNonEmptyList($parties);

        return new Covenant(
            commonDossier: $this->commonDossierViewFactory->make($dossier),
            dateFrom: $dossier->getDateFrom(),
            dateTo: $dossier->getDateTo(),
            previousVersionLink: $dossier->getPreviousVersionLink(),
            parties: $parties,
        );
    }
}
