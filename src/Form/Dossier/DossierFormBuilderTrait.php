<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Subject\Subject;
use App\Entity\Department;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

trait DossierFormBuilderTrait
{
    private function addInternalReferenceField(FormBuilderInterface $builder): void
    {
        $builder->add('internal_reference', TextType::class, [
            'label' => 'admin.dossiers.form.details.internal_reference_label',
            'required' => false,
            'help' => 'admin.dossiers.form.details.internal_reference_help',
            'attr' => [
                'class' => 'bhr-input-text w-full',
            ],
            'empty_data' => '',
        ]);
    }

    private function addDepartmentsField(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('departments', EntityType::class, [
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

    private function addSubjectField(
        FormBuilderInterface $builder,
        string $helpLabel = 'admin.dossiers.form.details.subject_help',
    ): void {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('subject', EntityType::class, [
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

    private function addSubmits(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isNewOrConcept()) {
            $builder
                ->add('next', SubmitType::class, [
                    'label' => 'global.save_and_continue',
                    'attr' => [
                        'data-first-button' => true,
                    ],
                    'row_attr' => [
                        'class' => 'pt-0',
                    ],
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'global.save_draft',
                    'attr' => [
                        'class' => 'bhr-button--secondary',
                        'data-last-button' => true,
                    ],
                ]);
        } else {
            $builder
                ->add('save', SubmitType::class, [
                    'label' => 'global.save_edit',
                    'attr' => [
                        'data-first-button' => true,
                    ],
                ])
                ->add('cancel', SubmitType::class, [
                    'label' => 'global.cancel',
                    'validate' => false,
                    'attr' => [
                        'class' => 'bhr-button--secondary',
                        'data-last-button' => true,
                    ],
                ]);
        }
    }

    private function addSaveAndPublishSubmit(FormBuilderInterface $builder): void
    {
        $builder->add('submit', SubmitType::class, [
            'label' => 'global.save_and_publish',
        ]);
    }

    /**
     * These fields are only added when creating a new dossier. They are not shown when editing an existing dossier.
     */
    private function addNewDossierFields(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isNew()) {
            $builder
                ->add('dossierNr', TextType::class, [
                    'label' => 'global.ref_number',
                    'required' => true, // @codingStandardsIgnoreStart
                    'help' => 'admin.dossiers.form.details.ref_nr_help', // @codingStandardsIgnoreEnd
                    'help_html' => true,
                    'empty_data' => '',
                    'attr' => [
                        'class' => 'bhr-input-text w-full',
                    ],
                ])
                ->add('documentPrefix', DocumentPrefixType::class, [
                    'label' => false,
                    'error_mapping' => [
                        '.' => 'documentPrefix',
                    ],
                ]);
        }
    }

    private function addSummaryField(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder->add('summary', TextareaType::class, [
            'label' => 'admin.dossiers.' . $dossier->getType()->value . '.summary',
            'required' => true,
            'attr' => ['rows' => 5],
            'empty_data' => '',
        ]);
    }

    private function addDocumentField(FormBuilderInterface $builder): void
    {
        $builder->add('document', DocumentType::class);
    }

    private function addTitleField(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder->add('title', TextType::class, [
            'label' => 'admin.dossiers.' . $dossier->getType()->value . '.form.details.title',
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
            'help' => 'admin.dossiers.' . $dossier->getType()->value . '.form.details.description',
            'help_html' => true,
            'attr' => [
                'class' => 'w-full',
            ],
            'empty_data' => '',
        ]);
    }

    private function addPublicationDateField(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder->add('publication_date', DateType::class, [
            'label' => 'admin.dossiers.' . $dossier->getType()->value . '.form.publication.publication_date_label',
            'help' => 'admin.dossiers.' . $dossier->getType()->value . '.form.publication.publication_date_help',
            'required' => true,
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'data' => $dossier->getPublicationDate() ?? new \DateTimeImmutable(),
            'constraints' => [
                new NotBlank(),
                new GreaterThanOrEqual(
                    new \DateTimeImmutable('today midnight'),
                    message: 'publication_date_must_be_today_or_future'
                ),
            ],
        ]);
    }

    private function addDateField(FormBuilderInterface $builder): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('date', DateType::class, [
                'required' => true,
                'label' => 'admin.dossiers.' . $dossier->getType()->value . '.form.details.date_label',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'property_path' => 'dateFrom',
            ]);
    }
}
