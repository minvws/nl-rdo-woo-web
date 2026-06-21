<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Form\PlainDateType;
use Shared\Form\Transformer\StringToDossierTitleTransformer;
use Shared\Service\Security\Roles;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
use Shared\Validator\UniqueDossierNr;
use Shared\ValueObject\PlainDate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Webmozart\Assert\Assert;

use function sprintf;

final readonly class DossierForm
{
    public function __construct(
        private FormBuilderInterface $formBuilder,
        private Security $security,
    ) {
    }

    public function addDateField(): void
    {
        $dossier = $this->getDossier();

        $this->formBuilder->add('date', PlainDateType::class, [
            'required' => true,
            'label' => sprintf('admin.dossiers.%s.form.details.date_label', $dossier->getType()->value),
            'property_path' => 'dateFrom',
            'widget' => 'single_text',
        ]);
    }

    public function addDepartmentsField(): void
    {
        $dossier = $this->getDossier();

        $this->formBuilder->add('departments', EntityType::class, [
            'class' => Department::class,
            'choices' => $dossier->getOrganisation()->getDepartments(),
            'attr' => [
                'class' => 'min-w-full',
            ],
            'required' => true,
            'multiple' => true,
            'choice_label' => 'name',
        ]);
    }

    public function addDocumentField(): void
    {
        $this->formBuilder->add('document', DocumentType::class);
    }

    public function addDocumentPrefixField(): void
    {
        $dossier = $this->getDossier();

        if ($dossier->getStatus()->isNew()) {
            $this->formBuilder->add('documentPrefix', DocumentPrefixType::class, [
                'label' => false,
                'error_mapping' => [
                    '.' => 'documentPrefix',
                ],
            ]);
        }
    }

    public function addDossierNrField(): void
    {
        $dossier = $this->getDossier();

        if ($dossier->getStatus()->isNewOrConcept() || $this->security->isGranted(Roles::ROLE_ORGANISATION_ADMIN)) {
            $this->formBuilder->add('dossierNr', TextType::class, [
                'label' => 'global.ref_number',
                'required' => true,
                'help' => 'admin.dossiers.form.details.ref_nr_help',
                'help_html' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'bhr-input-text w-full',
                ],
                'constraints' => [
                    new UniqueDossierNr(
                        documentPrefix: $dossier->getDocumentPrefix(),
                        excludeId: $dossier->getId(),
                        groups: [
                            DossierValidationGroup::DETAILS->value,
                            DossierValidationGroup::WORKFLOW_SCHEDULE_PUBLISH->value,
                            DossierValidationGroup::WORKFLOW_PUBLISH_AS_PREVIEW->value,
                            DossierValidationGroup::WORKFLOW_PUBLISH->value,
                        ],
                    ),
                ],
            ]);
        }
    }

    public function addInternalReferenceField(): void
    {
        $this->formBuilder->add('internal_reference', TextType::class, [
            'label' => 'admin.dossiers.form.details.internal_reference_label',
            'required' => false,
            'help' => 'admin.dossiers.form.details.internal_reference_help',
            'attr' => [
                'class' => 'bhr-input-text w-full',
            ],
            'empty_data' => '',
            'property_path' => 'internalReference',
        ]);
    }

    public function addPublicationDateField(): void
    {
        $dossier = $this->getDossier();

        $this->formBuilder->add('publication_date', PlainDateType::class, [
            'label' => sprintf('admin.dossiers.%s.form.publication.publication_date_label', $dossier->getType()->value),
            'help' => sprintf('admin.dossiers.%s.form.publication.publication_date_help', $dossier->getType()->value),
            'required' => true,
            'widget' => 'single_text',
            'data' => $dossier->getPublicationDate() ?? PlainDate::today(),
            'constraints' => [
                new NotBlank(),
                new PlainDateAfterOrEqual(
                    'today',
                    message: 'publication_date_must_be_today_or_future',
                ),
            ],
        ]);
    }

    public function addSaveAndPublishSubmit(): void
    {
        $this->formBuilder->add('submit', SubmitType::class, [
            'label' => 'global.save_and_publish',
        ]);
    }

    public function addSubjectField(
        string $helpLabel = 'admin.dossiers.form.details.subject_help',
    ): void {
        $dossier = $this->getDossier();

        $this->formBuilder->add('subject', EntityType::class, [
            'class' => Subject::class,
            'label' => 'admin.dossiers.form.details.subject_label',
            'help' => $helpLabel,
            'choices' => $dossier->getOrganisation()->getSubjects(),
            'attr' => [
                'class' => 'min-w-full',
            ],
            'required' => false,
            'placeholder' => 'admin.dossiers.form.details.subject_placeholder',
            'choice_label' => 'name',
        ]);
    }

    public function addSubmits(): void
    {
        $dossier = $this->getDossier();

        if ($dossier->getStatus()->isNewOrConcept()) {
            $this->formBuilder->add('next', SubmitType::class, [
                'label' => 'global.save_and_continue',
                'attr' => [
                    'data-first-button' => true,
                ],
                'row_attr' => [
                    'class' => 'pt-0',
                ],
            ]);
            $this->formBuilder->add('save', SubmitType::class, [
                'label' => 'global.save_draft',
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
                    'data-last-button' => true,
                ],
            ]);
        } else {
            $this->formBuilder->add('save', SubmitType::class, [
                'label' => 'global.save_edit',
                'attr' => [
                    'data-first-button' => true,
                ],
            ]);
            $this->formBuilder->add('cancel', SubmitType::class, [
                'label' => 'global.cancel',
                'validate' => false,
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
                    'data-last-button' => true,
                ],
            ]);
        }
    }

    public function addSummaryField(): void
    {
        $dossier = $this->getDossier();

        $this->formBuilder->add('summary', TextareaType::class, [
            'label' => sprintf('admin.dossiers.%s.summary', $dossier->getType()->value),
            'required' => true,
            'attr' => ['rows' => 5],
            'empty_data' => '',
        ]);
    }

    public function addTitleField(): void
    {
        $dossier = $this->getDossier();

        $this->formBuilder->add('title', TextType::class, [
            'label' => sprintf('admin.dossiers.%s.form.details.title', $dossier->getType()->value),
            'required' => true,
            /*
             * The translation below contains the following class names:
             * bhr-form-help__do
             * bhr-form-help__dont
             * my-4
             * text-sm
             *
             * We mention these here so that Tailwind will pick them up.
             */
            'help' => sprintf('admin.dossiers.%s.form.details.description', $dossier->getType()->value),
            'help_html' => true,
            'attr' => [
                'class' => 'w-full',
            ],
            'empty_data' => '',
        ]);
        $this->formBuilder->get('title')->addModelTransformer(new StringToDossierTitleTransformer());
    }

    public function getDossier(): AbstractDossier
    {
        $dossier = $this->formBuilder->getData();
        Assert::isInstanceOf($dossier, AbstractDossier::class);

        return $dossier;
    }
}
