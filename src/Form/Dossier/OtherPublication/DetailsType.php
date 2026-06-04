<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\OtherPublication;

use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
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
        return OtherPublication::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addTitleField($builder);
        $this->addDateField($builder);
        $this->addInternalReferenceField($builder);
        $this->addDepartmentsField($builder);
        $this->addSubjectField($builder, 'admin.dossiers.other_publication.form.details.subject_help');
        $this->addDossierNrField($builder, $this->security->isGranted(Roles::ROLE_ORGANISATION_ADMIN));
        $this->addDocumentPrefixField($builder);
        $this->addSubmits($builder);
    }
}
