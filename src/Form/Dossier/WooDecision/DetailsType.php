<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Entity\Department;
use App\Entity\Dossier;
use App\Entity\User;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Dossier\DocumentPrefixType;
use App\Form\Transformer\TextToArrayTransformer;
use App\Form\YearMonthType;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DetailsType extends AbstractDossierStepType
{
    public function getDataClass(): string
    {
        return Dossier::class;
    }

    public function __construct(
        private readonly Security $security,
        private readonly AuthorizationMatrix $authMatrix,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'publication.dossier.description.title',
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
                'help' => 'publication.dossier.description.help',
                'help_html' => true,
                'attr' => [
                    'class' => 'w-full',
                ],
                'empty_data' => '',
            ])
            ->add('date_from', YearMonthType::class, [
                'label' => 'Van',
                'row_attr' => [
                    'data-fieldset' => 'date_from date_to',
                    'data-legend' => 'Periode waarop verzoek ziet',
                    'data-required' => false,
                ],
                'required' => false,
                'placeholder' => 'kies beginmaand',
                YearMonthType::DAY_MODE => YearMonthType::MODE_FROM,
                'property_path' => 'dateFrom',
            ])
            ->add('date_to', YearMonthType::class, [
                'label' => 'tot en met',
                'required' => false,
                'placeholder' => 'kies eindmaand',
                YearMonthType::DAY_MODE => YearMonthType::MODE_TO,
                YearMonthType::REVERSE => true,
                'property_path' => 'dateTo',
            ])
            // Used as a placeholder to make sure the department is at the correct order
            ->add('departments', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('publication_reason', EnumType::class, [
                'label' => 'publication.dossier.description.category.title',
                'class' => PublicationReason::class,
                'expanded' => true,
                'required' => true,
            ])
            ->add('default_subjects', ChoiceType::class, [
                'label' => 'Standaard onderwerp',
                'required' => false, // @codingStandardsIgnoreStart
                'help' => 'publication.dossier.description.category.help', // @codingStandardsIgnoreEnd
                'choices' => [
                    'Opstart Corona' => 'Opstart Corona',
                    'Overleg VWS' => 'Overleg VWS',
                    'Overleg overig' => 'Overleg overig',
                    'RIVM' => 'RIVM',
                    'Digitale middelen' => 'Digitale middelen',
                    'Besmettelijkheid kinderen' => 'Besmettelijkheid kinderen',
                    'Scenario’s en maatregelen' => 'Scenario’s en maatregelen',
                    'Medische hulpmiddelen' => 'Medische hulpmiddelen',
                    'Capaciteit ziekenhuis' => 'Capaciteit ziekenhuis',
                    'Testen' => 'Testen',
                    'Vaccinaties en medicatie' => 'Vaccinaties en medicatie',
                ],
                'placeholder' => 'Kies een onderwerp',
            ]);

        $this->addDepartmentField($builder);

        $this->addNewDossierFields($builder);

        $this->addSubmits($builder);

        $this->addTransformers($builder);

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

    protected function addTransformers(FormBuilderInterface $builder): void
    {
        // Default subjects is a text field, but holds semicolon separated files
        $builder->get('default_subjects')->addModelTransformer(new ReversedTransformer(new TextToArrayTransformer(';')), forceAppend: true);
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
                    'label' => 'Referentienummer besluit',
                    'required' => true, // @codingStandardsIgnoreStart
                    'help' => 'Minimaal 3 en maximaal 50 karakters. Gebruik alleen letters, cijfers en verbindingstekens. <strong>Let op</strong>: dit nummer is na het opslaan van de basisgegevens niet meer aan te passen.', // @codingStandardsIgnoreEnd
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
                    'label' => 'Opslaan en verder',
                    'attr' => [
                        'data-first-button' => true,
                    ],
                    'row_attr' => [
                        'class' => 'pt-0',
                    ],
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'Concept opslaan',
                    'attr' => [
                        'class' => 'bhr-button--secondary',
                        'data-last-button' => true,
                    ],
                ]);
        } else {
            $builder
                ->add('save', SubmitType::class, [
                    'label' => 'Bewerken en opslaan',
                    'attr' => [
                        'data-first-button' => true,
                    ],
                ])
                ->add('cancel', SubmitType::class, [
                    'label' => 'Annuleren',
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
                    'class' => 'bhr-input-select w-full',
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
