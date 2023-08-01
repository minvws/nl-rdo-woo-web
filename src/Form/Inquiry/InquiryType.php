<?php

declare(strict_types=1);

namespace App\Form\Inquiry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<InquiryType>
 */
class InquiryType extends AbstractType
{
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
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
