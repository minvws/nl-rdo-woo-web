<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\Advice;

use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return Advice::class;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTitleField($builder);
        $this->addDateField($builder);
        $this->addInternalReferenceField($builder);
        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder, 'admin.dossiers.advice.form.details.subject_help');
        $this->addDossierNrField($builder);
        $this->addDocumentPrefixField($builder);
        $this->addSubmits($builder);
    }
}
