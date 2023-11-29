<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Department;
use App\Entity\Dossier;
use App\Entity\GovernmentOfficial;
use App\Entity\User;
use App\Form\Transformer\EntityToArrayTransformer;
use App\Form\Transformer\TextToArrayTransformer;
use App\Form\YearMonthType;
use App\Roles;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DetailsType extends AbstractDossierStepType
{
    public function __construct(
        private readonly Security $security
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
                'label' => 'Description of the decision',
                'required' => true, // @codingStandardsIgnoreStart
                'help' => 'Keep it short and be specific. So do not use unnecessary prepositions, articles and obvious substantive words such as Woo, decision, etc.<ul class="my-4"><li class="bhr-form-help__dont text-sm">Not: Woo decision regarding the discussion between the ministry and a number of hospitals about the initial situation of the number of IC beds available</li><li class="bhr-form-help__do text-sm">Well: Discussion between the ministry and hospitals about the initial situation of the number of IC beds</li></ ul>', // @codingStandardsIgnoreEnd
                'help_html' => true,
                'attr' => [
                    'class' => 'w-full',
                    'placeholder' => 'Give a name to the decision',
                ],
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 500]),
                ],
            ])
            ->add('date_from', YearMonthType::class, [
                'label' => 'Periode vanaf (optioneel)',
                'row_attr' => [
                    'data-fieldset' => 'date_from date_to',
                ],
                'required' => false,
                'placeholder' => 'kies beginmaand',
                'day_mode' => YearMonthType::MODE_FROM,
            ])
            ->add('date_to', YearMonthType::class, [
                'label' => 'tot en met (optioneel)',
                'required' => false,
                'placeholder' => 'kies eindmaand',
                'day_mode' => YearMonthType::MODE_TO,
                'constraints' => [
                    new GreaterThanOrEqual([
                        'propertyPath' => 'parent.all[date_from].data',
                    ]),
                ],
            ])
            // Used as a placeholder to make sure the department is at the correct order
            ->add('departments', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('governmentofficials', EntityType::class, [
                'class' => GovernmentOfficial::class,
                'label' => 'Bewindspersoon',
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name',
                'placeholder' => 'Kies een bewindspersoon',
                'constraints' => [
                    new All([
                        'constraints' => [new NotBlank()],
                    ]),
                ],
            ])
            ->add('publication_reason', ChoiceType::class, [
                'label' => 'Choose the type of decision',
                'choices' => [
                    'Wob-verzoek' => Dossier::REASON_WOB_REQUEST,
                    'Woo-verzoek' => Dossier::REASON_WOO_REQUEST,
                    // Not in use for now
                    // 'Woo-actieve openbaarmaking' => Dossier::REASON_WOO_ACTIVE,
                ],
                'expanded' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('default_subjects', ChoiceType::class, [
                'label' => 'Standaard onderwerp (optioneel)',
                'required' => false, // @codingStandardsIgnoreStart
                'help' => 'Choose the category under which this decision falls. The chosen topic will also be linked to all documents in this decision.', // @codingStandardsIgnoreEnd
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
                    'Vaccinaties & medicatie' => 'Vaccinaties & medicatie',
                ],
                'placeholder' => 'Kies een onderwerp',
            ])
        ;

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

        // If we are editing an entity, we need to transform the entity to an array if the choice is not multiple. This is because the dossier
        // entity always expects an array of entities, even if the choice is not multiple.
        if ($builder->get('governmentofficials')->getOption('multiple') === false) {
            $builder->get('governmentofficials')->addModelTransformer(new ReversedTransformer(new EntityToArrayTransformer()), forceAppend: true);
        }
    }

    /**
     * These fields are added when creating a new dossier. They are not shown when editing an existing dossier.
     */
    private function addNewDossierFields(FormBuilderInterface $builder): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getId() === null) {
            $builder
                ->add('dossierNr', TextType::class, [
                    'label' => 'Referentienummer besluit',
                    'required' => true,
                    'help' => 'Let op, je kan deze na het opslaan van de basisgegevens niet meer aanpassen.',
                    'attr' => [
                        'placeholder' => 'Vul het nummer in',
                    ],
                    'empty_data' => '',
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 3, 'max' => 50]),
                        new Regex([
                            'pattern' => '/^[a-z0-9-]+$/i',
                            'message' => 'Gebruik alleen letters, cijfers en verbindingstekens',
                        ]),
                    ],
                ])
                ->add('documentPrefix', DocumentPrefixType::class, [
                    'label' => false,
                ]);
        }
    }

    private function addSubmits(FormBuilderInterface $builder): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getId() === null || $dossier->getStatus() === Dossier::STATUS_CONCEPT) {
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
                'label' => 'Verantwoordelijke bestuursorgaan',
                'required' => true,
                'multiple' => false,
                // We use a non-mapped field, and deal with the data in the event listeners.
                'mapped' => false,
                'choice_label' => 'name_and_short',
                'placeholder' => 'Kies een bestuursorgaan',
                'constraints' => [
                    new NotBlank(),
                ],
            ];

            /** @var User $user */
            if ($user->hasRole(Roles::ROLE_SUPER_ADMIN)) {
                $options['query_builder'] = function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')->select('d')->orderBy('d.name', 'ASC');
                };

                $form->add('departments', EntityType::class, $options);

                return;
            }

            // If we have more than one entity, we need to use a choice type
            $departments = [$user->getOrganisation()->getDepartment()];
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
