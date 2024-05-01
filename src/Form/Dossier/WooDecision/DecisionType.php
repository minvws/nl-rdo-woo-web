<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Step\StepName;
use App\Entity\Dossier;
use App\Form\Dossier\AbstractDossierStepType;
use App\Form\Transformer\NullToEmptyStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class DecisionType extends AbstractDossierStepType
{
    protected const DOCUMENT_MIMETYPES = [
        'application/pdf',
    ];

    public function getDataClass(): string
    {
        return Dossier::class;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus()->isConcept()) {
            $builder->add('decision', ChoiceType::class, [
                'label' => 'Besluit',
                'required' => true,
                'help' => 'Hoe komen we tegemoet aan het verzoek?',
                'choices' => [
                    'Gedeeltelijke openbaarmaking' => Dossier::DECISION_PARTIAL_PUBLIC,
                    'Reeds openbaar' => Dossier::DECISION_ALREADY_PUBLIC,
                    'Openbaarmaking' => Dossier::DECISION_PUBLIC,
                    'Geen openbaarmaking' => Dossier::DECISION_NOT_PUBLIC,
                    'Niets aangetroffen' => Dossier::DECISION_NOTHING_FOUND,
                ],
                'expanded' => true,
            ]);
            $builder->get('decision')->addModelTransformer(new NullToEmptyStringTransformer());
        }

        $builder
            ->add('summary', TextareaType::class, [
                'label' => 'Samenvatting van het besluit',
                'required' => true,
                'help' => 'Vat samen waarom het bovenstaande besluit is genomen',
                'attr' => ['rows' => 5],
                'empty_data' => '',
            ]);

        $uploadRequired = false;
        $uploadConstraints = [
            new File(
                [
                    'mimeTypes' => self::DOCUMENT_MIMETYPES,
                    'mimeTypesMessage' => 'Gebruik een document van het type PDF',
                ],
                groups: [StepName::DECISION->value]
            ),
        ];

        // When there is no decision document uploaded yet this field is mandatory
        if (! $dossier->getDecisionDocument()?->getFileInfo()->isUploaded()) {
            $uploadRequired = true;
        }

        $builder
            ->add('decision_document', FileType::class, [
                'label' => 'Officiële besluitbrief',
                'mapped' => false,
                'required' => $uploadRequired,
                'constraints' => $uploadConstraints,
                'attr' => [
                    'accept' => self::DOCUMENT_MIMETYPES,
                ],
                'property_path' => 'decisionDocument',
            ]);

        $builder
            ->add('decision_date', DateType::class, [
                'required' => true,
                'label' => 'Datum waarop het besluit genomen is',
                'help' => 'Vul de formele besluit-datum in',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'data' => $dossier->getDecisionDate() ?? new \DateTimeImmutable(),
                'property_path' => 'decisionDate',
            ]);

        $this->addSubmits($dossier, $builder);
    }

    public function addSubmits(Dossier $dossier, FormBuilderInterface $builder): void
    {
        if ($dossier->getStatus()->isConcept()) {
            $builder
                ->add('next', SubmitType::class, [
                    'label' => 'Opslaan en verder',
                    'attr' => [
                        'data-first-button' => true,
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
                    'attr' => [
                        'class' => 'bhr-button--secondary',
                        'data-last-button' => true,
                    ],
                    'validate' => false,
                ]);
        }
    }
}
