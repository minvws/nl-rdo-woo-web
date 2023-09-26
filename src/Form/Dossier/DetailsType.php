<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Department;
use App\Entity\DocumentPrefix;
use App\Entity\Dossier;
use App\Entity\GovernmentOfficial;
use App\Form\Transformer\DocumentPrefixTransformer;
use App\Form\Transformer\EntityToArrayTransformer;
use App\Form\Transformer\TextToArrayTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DetailsType extends AbstractDossierStepType
{
    public function __construct(
        private readonly EntityManagerInterface $doctrine,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Omschrijving van het besluit    ',
                'required' => true,
                'help' => 'Geef een korte titel voor het dossier',
                'attr' => [
                    'class' => 'w-full',
                    'placeholder' => 'Vul de omschrijving van het besluit in',
                ],
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('date_from', DateType::class, [
                'label' => 'Periode vanaf (optioneel)',
                'required' => false,
                'help' => 'Van',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('date_to', DateType::class, [
                'label' => 'Periode tot en met (optioneel)',
                'required' => false,
                'help' => 'T/m',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('departments', EntityType::class, [
                'class' => Department::class,
                'label' => 'Verantwoordelijke organisatie',
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name_and_short',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.name', 'ASC');
                },
                'placeholder' => 'Kies een organisatie',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('governmentofficials', EntityType::class, [
                'class' => GovernmentOfficial::class,
                'label' => 'Bewindspersoon',
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name',
                'placeholder' => 'Kies een bewindspersoon',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('publication_reason', ChoiceType::class, [
                'label' => 'Publicatie reden',
                'help' => 'De reden waarom dit dossier gepubliceerd wordt',
                'choices' => [
                    'Wob-verzoek' => Dossier::REASON_WOB_REQUEST,
                    'Woo-verzoek' => Dossier::REASON_WOO_REQUEST,
                    'Woo-actieve openbaarmaking' => Dossier::REASON_WOO_ACTIVE,
                ],
                'expanded' => true,
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('default_subjects', TextType::class, [
                'label' => 'Default subject',
                'required' => true,
                'help' => 'Onderwerp dat standaard aan documenten binnen dit dossier worden toegevoegd indien er geen onderwerp is meegeven',
                'constraints' => [
                    new NotBlank(),
                ],
            ]);

        $this->addNewDossierFields($builder);

        $this->addSubmits($builder);

        $this->addTransformers($builder);
    }

    protected function addTransformers(FormBuilderInterface $builder): void
    {
        // Default subjects is a text field, but holds semicolon separated files
        $builder->get('default_subjects')->addModelTransformer(new ReversedTransformer(new TextToArrayTransformer(';')), forceAppend: true);

        // If we are editing an entity, we need to transform the entity to an array if the choice is not multiple. This is because the dossier
        // entity always expects an array of entities, even if the choice is not multiple.
        if ($builder->get('departments')->getOption('multiple') === false) {
            $builder->get('departments')->addModelTransformer(new ReversedTransformer(new EntityToArrayTransformer()), forceAppend: true);
        }
        if ($builder->get('governmentofficials')->getOption('multiple') === false) {
            $builder->get('governmentofficials')->addModelTransformer(new ReversedTransformer(new EntityToArrayTransformer()), forceAppend: true);
        }

        // Transform the DocumentPrefix entity to a string
        if ($builder->has('documentPrefix')) {
            $builder->get('documentPrefix')->addModelTransformer(new DocumentPrefixTransformer($this->doctrine));
        }
    }

    private function addNewDossierFields(FormBuilderInterface $builder): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getId() === null) {
            $builder
                ->add('dossier_nr', TextType::class, [
                    'label' => 'Referentienummer besluit',
                    'required' => true,
                    'help' => 'Verplicht dossier nummer. Let op: dit is na opslaan van de basisgegevens niet meer aan te passen.',
                    'attr' => [
                        'class' => 'w-full',
                        'placeholder' => 'Vul het nummer in',
                    ],
                    'constraints' => [
                        new NotBlank(),
                        new Length(['min' => 3, 'max' => 50]),
                        new Regex([
                            'pattern' => '/^[a-z0-9-]+$/i',
                            'message' => 'Gebruik alleen letters, cijfers en verbindingstekens',
                        ]),
                    ],
                ])
                ->add('documentPrefix', EntityType::class, [
                    'class' => DocumentPrefix::class,
                    'label' => 'Prefix voor documenten',
                    'choice_label' => 'prefix_and_description',
                    'required' => true,
                    'help' => 'Deze voegen we automatisch toe aan de bestandsnaam van documenten. '
                        . 'Let op: dit is na opslaan van de basisgegevens niet meer aan te passen.',
                    'placeholder' => 'Selecteer een prefix',
                    'constraints' => [
                        new NotBlank(),
                    ],
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
                    'attr' => ['class' => 'data-first-button'],
                ])
                ->add('save', SubmitType::class, [
                    'label' => 'Concept opslaan',
                    'attr' => ['class' => 'data-last-button'],
                ]);
        } else {
            $builder
                ->add('save', SubmitType::class, [
                    'label' => 'Bewerken en opslaan',
                    'attr' => ['class' => 'data-first-button'],
                ])
                ->add('cancel', SubmitType::class, [
                    'label' => 'Annuleren',
                    'validate' => false,
                    'attr' => ['class' => 'data-last-button'],
                ]);
        }
    }
}
