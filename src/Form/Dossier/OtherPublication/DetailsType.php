<?php

declare(strict_types=1);

namespace App\Form\Dossier\OtherPublication;

use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return OtherPublication::class;
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
        $this->addSubjectField($builder, 'admin.dossiers.other_publication.form.details.subject_help');
        $this->addNewDossierFields($builder);
        $this->addSubmits($builder);
    }
}
