<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\InvestigationReport;

use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return InvestigationReport::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTitleField($builder);
        $this->addDateField($builder);
        $this->addInternalReferenceField($builder);
        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder);
        $this->addDossierNrField($builder);
        $this->addDocumentPrefixField($builder);
        $this->addSubmits($builder);
    }
}
