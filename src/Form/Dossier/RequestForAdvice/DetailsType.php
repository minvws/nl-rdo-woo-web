<?php

declare(strict_types=1);

namespace App\Form\Dossier\RequestForAdvice;

use App\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DossierFormBuilderTrait;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function getDataClass(): string
    {
        return RequestForAdvice::class;
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
        $this->addSubjectField($builder, 'admin.dossiers.request_for_advice.form.details.subject_help');
        $this->addNewDossierFields($builder);
        $this->addSubmits($builder);
    }
}
