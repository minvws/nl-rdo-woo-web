<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Department;
use App\Entity\DocumentPrefix;
use App\Entity\Dossier;
use App\Entity\GovernmentOfficial;
use App\Form\Transformer\DocumentPrefixTransformer;
use App\Form\Transformer\EntityToArrayTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\ReversedTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/**
 * @template-extends AbstractType<DossierType>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DossierType extends AbstractType
{
    protected EntityManagerInterface $doctrine;

    protected const ACCEPTED_MIMETYPES = [
        'application/xls',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function __construct(EntityManagerInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titel',
                'required' => true,
                'help' => 'Geef een korte titel voor het dossier',
            ])
            ->add('summary', TextAreaType::class, [
                'label' => 'Omschrijving',
                'required' => true,
                'help' => 'Geef een korte omschrijving voor het dossier',
                'constraints' => [],
            ])
            ->add('departments', EntityType::class, [
                'class' => Department::class,
                'label' => 'Departement',
                'required' => false,
                'multiple' => false,
                'help' => 'Het departement waar het dossier onder hoort',
                'choice_label' => 'name',
            ])
            ->add('governmentofficials', EntityType::class, [
                'class' => GovernmentOfficial::class,
                'label' => 'Departementshoofd',
                'required' => false,
                'multiple' => false,
                'help' => 'De bewindsvoerder van het departement waar dit dossier onder hoort',
                'choice_label' => 'name',
            ])
            ->add('documentPrefix', EntityType::class, [
                'class' => DocumentPrefix::class,
                'choice_label' => 'prefix',
                'label' => 'Document prefix',
                'required' => true,
                'help' => 'Het document prefix bepaald onder welk domein de documenten van dit dossier vallen',
                'placeholder' => 'Selecteer een prefix',
            ])
            ->add('publication_reason', ChoiceType::class, [
                'label' => 'Publicatie reden',
                'required' => true,
                'help' => 'De reden waarom dit dossier gepubliceerd wordt',
                'choices' => [
                    'Wob: verzoek' => Dossier::REASON_WOB_REQUEST,
                    'Woo: verzoek' => Dossier::REASON_WOO_REQUEST,
                    'Woo: actieve openbaarmaking' => Dossier::REASON_WOO_ACTIVE,
                ],
            ])
            ->add('decision', ChoiceType::class, [
                'label' => 'Genomen besluit',
                'required' => true,
                'help' => 'Het besluit omtrent dit dossier',
                'choices' => [
                    'Reeds openbaar' => Dossier::DECISION_ALREADY_PUBLIC,
                    'Openbaar' => Dossier::DECISION_PUBLIC,
                    'Gedeeltelijk openbaar' => Dossier::DECISION_PARTIAL_PUBLIC,
                    'Niet openbaar' => Dossier::DECISION_NOT_PUBLIC,
                    'Niets aangetroffen' => Dossier::DECISION_NOTHING_FOUND,
                ],
            ])
            ->add('date_from', DateType::class, [
                'label' => 'Periode van',
                'required' => false,
                'help' => 'De datum vanaf wanneer het dossier loopt',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('date_to', DateType::class, [
                'label' => 'Periode tot',
                'required' => false,
                'help' => 'De datum tot wanneer het dossier loopt',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('inventory', FileType::class, [
                'label' => 'Inventaris',
                'required' => false,
//                'required' => $options['edit_mode'] ? false : true,
                'help' => 'De inventaris van het dossier',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => self::ACCEPTED_MIMETYPES,
                        'mimeTypesMessage' => 'Please upload a valid spreadsheet',
                    ]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ]);

        $this->addTransformers($builder);
    }

    protected function addTransformers(FormBuilderInterface $builder): void
    {
        // If we are editing an entity, we need to transform the entity to an array if the choice is not multiple. This is because the dossier
        // entity always expects an array of entities, even if the choice is not multiple.
        if ($builder->get('departments')->getOption('multiple') == false) {
            $builder->get('departments')->addModelTransformer(new ReversedTransformer(new EntityToArrayTransformer()), forceAppend: true);
        }
        if ($builder->get('governmentofficials')->getOption('multiple') == false) {
            $builder->get('governmentofficials')->addModelTransformer(new ReversedTransformer(new EntityToArrayTransformer()), forceAppend: true);
        }

        // Transform the DocumentPrefix entity to a string
        $builder->get('documentPrefix')->addModelTransformer(new DocumentPrefixTransformer($this->doctrine));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dossier::class,
            'edit_mode' => false,       // Set to true if we are editting an entity.
        ]);
    }
}
