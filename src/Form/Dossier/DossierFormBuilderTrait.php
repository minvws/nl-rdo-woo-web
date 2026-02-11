<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use DateTimeImmutable;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Subject\Subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Webmozart\Assert\Assert;

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
            'property_path' => 'internalReference',
        ]);
    }

    private function addDepartmentsField(FormBuilderInterface $builder): void
    {
        $dossier = $this->getDossier($builder);

        $builder->add('departments', EntityType::class, [
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
        $dossier = $this->getDossier($builder);

        $builder->add('subject', EntityType::class, [
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
        $dossier = $this->getDossier($builder);

        if ($dossier->getStatus()->isNewOrConcept()) {
            $builder->add('next', SubmitType::class, [
                'label' => 'global.save_and_continue',
                'attr' => [
                    'data-first-button' => true,
                ],
                'row_attr' => [
                    'class' => 'pt-0',
                ],
            ]);
            $builder->add('save', SubmitType::class, [
                'label' => 'global.save_draft',
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
                    'data-last-button' => true,
                ],
            ]);
        } else {
            $builder->add('save', SubmitType::class, [
                'label' => 'global.save_edit',
                'attr' => [
                    'data-first-button' => true,
                ],
            ]);
            $builder->add('cancel', SubmitType::class, [
                'label' => 'global.cancel',
                'validate' => false,
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
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

    private function addDossierNrField(FormBuilderInterface $builder): void
    {
        $dossier = $this->getDossier($builder);

        if ($dossier->getStatus()->isNewOrConcept()) {
            $builder->add('dossierNr', TextType::class, [
                'label' => 'global.ref_number',
                'required' => true,
                'help' => 'admin.dossiers.form.details.ref_nr_help',
                'help_html' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'bhr-input-text w-full',
                ],
            ]);
        }
    }

    private function addDocumentPrefixField(FormBuilderInterface $builder): void
    {
        $dossier = $this->getDossier($builder);

        if ($dossier->getStatus()->isNew()) {
            $builder->add('documentPrefix', DocumentPrefixType::class, [
                'label' => false,
                'error_mapping' => [
                    '.' => 'documentPrefix',
                ],
            ]);
        }
    }

    private function addSummaryField(FormBuilderInterface $builder): void
    {
        $dossier = $this->getDossier($builder);

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
        $dossier = $this->getDossier($builder);

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
        $dossier = $this->getDossier($builder);

        $builder->add('publication_date', DateType::class, [
            'label' => 'admin.dossiers.' . $dossier->getType()->value . '.form.publication.publication_date_label',
            'help' => 'admin.dossiers.' . $dossier->getType()->value . '.form.publication.publication_date_help',
            'required' => true,
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'data' => $dossier->getPublicationDate() ?? new DateTimeImmutable(),
            'constraints' => [
                new NotBlank(),
                new GreaterThanOrEqual(
                    new DateTimeImmutable('today midnight'),
                    message: 'publication_date_must_be_today_or_future'
                ),
            ],
        ]);
    }

    private function addDateField(FormBuilderInterface $builder): void
    {
        $dossier = $this->getDossier($builder);

        $builder->add('date', DateType::class, [
            'required' => true,
            'label' => 'admin.dossiers.' . $dossier->getType()->value . '.form.details.date_label',
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'property_path' => 'dateFrom',
        ]);
    }

    private function getDossier(FormBuilderInterface $builder): AbstractDossier
    {
        $dossier = $builder->getData();
        Assert::isInstanceOf($dossier, AbstractDossier::class);

        return $dossier;
    }
}
