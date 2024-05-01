<?php

declare(strict_types=1);

namespace App\Form\Dossier\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Entity\Department;
use App\Entity\Dossier;
use App\Entity\User;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DocumentPrefixType;
use App\Form\YearMonthType;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DetailsType extends AbstractDossierStepType
{
    public function __construct(
        private readonly Security $security,
        private readonly AuthorizationMatrix $authMatrix,
    ) {
    }

    public function getDataClass(): string
    {
        return Covenant::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'admin.dossiers.convenant.form.details.title',
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
                'help' => 'admin.dossiers.convenant.form.details.description',
                'help_html' => true,
                'attr' => [
                    'class' => 'w-full',
                ],
                'empty_data' => '',
            ])
            ->add('date_from', YearMonthType::class, [
                'label' => 'global.date_from',
                'row_attr' => [
                    'data-fieldset' => 'date_from date_to',
                    'data-legend' => 'admin.dossiers.convenant.form.details.date_legend',
                    'data-required' => true,
                ],
                'required' => false,
                'placeholder' => 'global.date_from.placeholder',
                'day_mode' => YearMonthType::MODE_FROM,
                'property_path' => 'dateFrom',
            ])
            ->add('date_to', YearMonthType::class, [
                'label' => 'global.date_to',
                'required' => false,
                'placeholder' => 'global.date_to.placeholder',
                'day_mode' => YearMonthType::MODE_TO,
                'min_years' => 10,
                'plus_years' => 5,
                'property_path' => 'dateTo',
            ])
            ->add('internal_reference', TextType::class, [
                'label' => 'admin.dossiers.convenant.form.details.internal_reference_label',
                'required' => false,
                'help' => 'admin.dossiers.convenant.form.details.internal_reference_help',
                'help_html' => false,
                'attr' => [
                    'class' => 'bhr-input-text w-full',
                ],
                'empty_data' => '',
            ])

            // Used as a placeholder to make sure the department is at the correct order
            ->add('departments', HiddenType::class, [
                'mapped' => false,
            ])
        ;

        $this->addDepartmentField($builder);

        $this->addNewDossierFields($builder);

        $this->addSubmits($builder);

        // This event handler will handle the departments, as departments are many2many, but only one department should be selected (and used).
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            if ($form->has('departments')) {
                /** @var Dossier $dossier */
                $dossier = $event->getData();
                // Set the department to the first department in the list if more are available
                $form->get('departments')->setData($dossier->getDepartments()[0]);
            }
        });

        // This event handler will handle the departments, as departments are many2many, but only one department should be selected (and used).
        $builder->addEventListener(FormEvents::SUBMIT, listener: function (FormEvent $event) {
            $form = $event->getForm();

            if ($form->has('departments')) {
                /** @var Department $department */
                $department = $form->get('departments')->getData();
                /** @var Dossier $dossier */
                $dossier = $event->getData();
                // setdepartments() will clear any existing departments
                $dossier->setDepartments([$department]);

                $event->setData($dossier);
            }
        });
    }

    /**
     * These fields are added when creating a new dossier. They are not shown when editing an existing dossier.
     */
    private function addNewDossierFields(FormBuilderInterface $builder): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isNew()) {
            $builder
                ->add('dossierNr', TextType::class, [
                    'label' => 'admin.dossiers.convenant.form.details.ref_nr',
                    'required' => true, // @codingStandardsIgnoreStart
                    'help' => 'admin.dossiers.convenant.form.details.ref_nr_help', // @codingStandardsIgnoreEnd
                    'help_html' => true,
                    'empty_data' => '',
                ])
                ->add('documentPrefix', DocumentPrefixType::class, [
                    'label' => false,
                    'error_mapping' => [
                        '.' => 'documentPrefix',
                    ],
                ]);
        }
    }

    private function addSubmits(FormBuilderInterface $builder): void
    {
        /** @var Dossier $dossier */
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

    protected function addDepartmentField(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var User|UserInterface|null $user */
            $user = $this->security->getUser();
            if (! $user) {
                throw new \RuntimeException('User is not logged in');
            }

            $form = $event->getForm();

            $options = [
                'class' => Department::class,
                'label' => 'admin.publications.responsible_department',
                'required' => true,
                'multiple' => false,
                // We use a non-mapped field, and deal with the data in the event listeners.
                'mapped' => false,
                'choice_label' => 'name_and_short',
                'placeholder' => 'admin.publications.responsible_department_placeholder',
                'attr' => [
                    'class' => 'bhr-input-select w-3/6',
                ],
            ];

            // If we have more than one entity, we need to use a choice type
            $departments = [$this->authMatrix->getActiveOrganisation()->getDepartment()];
            if (count($departments) > 1) {
                $options['choices'] = $departments;
                $form->add('departments', EntityType::class, $options);

                return;
            }

            // One entity does not give us a choice, so we remove the placeholder
            unset($options['placeholder']);
            $options['choices'] = $departments;
            $form->add('departments', EntityType::class, $options);
        });
    }
}
