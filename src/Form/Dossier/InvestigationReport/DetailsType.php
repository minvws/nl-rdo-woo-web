<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\InvestigationReport;

use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormFactory;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    public function __construct(
        private readonly DossierFormFactory $dossierFormFactory,
    ) {
    }

    public function getDataClass(): string
    {
        return InvestigationReport::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $dossierForm = $this->dossierFormFactory->for($builder);
        $dossierForm->addTitleField();
        $dossierForm->addDateField();
        $dossierForm->addInternalReferenceField();
        $dossierForm->addDepartmentsField();
        $dossierForm->addSubjectField();
        $dossierForm->addDossierNrField();
        $dossierForm->addDocumentPrefixField();
        $dossierForm->addSubmits();
    }
}
