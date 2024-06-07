<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant as CovenantEntity;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Webmozart\Assert\Assert;

final readonly class CovenantViewFactory
{
    public function __construct(
        private DepartmentViewFactory $departmentViewFactory,
    ) {
    }

    public function make(CovenantEntity $dossier): Covenant
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        $parties = $dossier->getParties();
        Assert::isNonEmptyList($parties);

        $mainDepartment = $dossier->getDepartments()->first();
        Assert::notFalse($mainDepartment);

        return new Covenant(
            dossierId: $dossier->getId()->toRfc4122(),
            dossierNr: $dossier->getDossierNr(),
            documentPrefix: $dossier->getDocumentPrefix(),
            isPreview: $dossier->getStatus()->isPreview(),
            title: $title,
            pageTitle: $dossier->getStatus()->isPreview()
                ? sprintf('%s %s', $title, '(preview)')
                : $title,
            publicationDate: $publicationDate,
            mainDepartment: $this->departmentViewFactory->make($mainDepartment),
            summary: $dossier->getSummary(),
            type: $dossier->getType(),
            dateFrom: $dossier->getDateFrom(),
            dateTo: $dossier->getDateTo(),
            previousVersionLink: $dossier->getPreviousVersionLink(),
            parties: $parties,
        );
    }
}
