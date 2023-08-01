<?php

declare(strict_types=1);

namespace App\Form\Inquiry;

use App\Entity\Dossier;
use App\Form\ChoiceLoader\EntityChoiceLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<InquiryEditType>
 */
class InquiryEditType extends AbstractType
{
    protected EntityManagerInterface $doctrine;

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
            ->add('applicant', TextType::class, [
                'label' => 'Aanvrager',
                'required' => true,
                'help' => 'De naam van de aanvrager',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Omschrijving',
                'required' => true,
                'help' => 'Geef een gedetailleerde omschrijving van het verzoek',
            ])
            ->add('dossier', ChoiceType::class, [
                'label' => 'Dossier',
                'required' => false,
                'help' => 'Het dossier waar het verzoek bij hoort',
                'choice_loader' => new EntityChoiceLoader($this->doctrine, Dossier::class, function (Dossier $entity) {
                    return 'Dossier ' . $entity->getDossierNr() . ' - ' . $entity->getTitle();
                }),
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => true,
                'help' => 'De status van het verzoek',
                'choices' => [
                    'Nieuw' => 'new',
                    'In behandeling' => 'in_progress',
                    'Afgehandeld' => 'closed',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }
}
