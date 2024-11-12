<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ViewModel;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\ViewModel\DepartmentViewFactory;
use Webmozart\Assert\Assert;

readonly class CommonDossierPropertiesViewFactory
{
    public function __construct(
        private DepartmentViewFactory $departmentViewFactory,
        private SubjectViewFactory $subjectViewFactory,
    ) {
    }

    public function make(AbstractDossier $dossier): CommonDossierProperties
    {
        $title = $dossier->getTitle();
        Assert::notNull($title);

        $publicationDate = $dossier->getPublicationDate();
        Assert::notNull($publicationDate);

        $mainDepartment = $dossier->getDepartments()->first();
        Assert::notFalse($mainDepartment);

        return new CommonDossierProperties(
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
            subject: $this->subjectViewFactory->getSubjectForDossier($dossier),
        );
    }
}
