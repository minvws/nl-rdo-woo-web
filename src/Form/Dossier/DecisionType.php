<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Dossier;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class DecisionType extends AbstractDossierStepType
{
    protected const DOCUMENT_MIMETYPES = [
        'application/pdf',
    ];

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('decision', ChoiceType::class, [
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
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Samenvatting van het besluit',
                'required' => true,
                'help' => 'Vat samen waarom het bovenstaande besluit is genomen',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('decision_document', FileType::class, [
                'label' => 'Officiële besluitbrief',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => self::DOCUMENT_MIMETYPES,
                        'mimeTypesMessage' => 'Gebruik een document van het type PDF',
                    ]),
                ],
            ]);

        $this->addSubmits($builder);
    }

    public function addSubmits(FormBuilderInterface $builder): void
    {
        /** @var Dossier $dossier */
        $dossier = $builder->getData();

        if ($dossier->getStatus() === Dossier::STATUS_CONCEPT) {
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
                    'attr' => ['class' => 'data-last-button'],
                    'validate' => false,
                ]);
        }
    }
}
