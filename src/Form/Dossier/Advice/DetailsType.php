<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\Advice;

use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Form\Dossier\AbstractDossierStepType;
use Shared\Form\Dossier\DossierFormBuilderTrait;
use Shared\Service\Security\Roles;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormBuilderInterface;

class DetailsType extends AbstractDossierStepType
{
    use DossierFormBuilderTrait;

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function getDataClass(): string
    {
        return Advice::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTitleField($builder);
        $this->addDateField($builder);
        $this->addInternalReferenceField($builder);
        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder, 'admin.dossiers.advice.form.details.subject_help');
        $this->addDossierNrField($builder, $this->security->isGranted(Roles::ROLE_ORGANISATION_ADMIN));
        $this->addDocumentPrefixField($builder);
        $this->addSubmits($builder);
    }
}
