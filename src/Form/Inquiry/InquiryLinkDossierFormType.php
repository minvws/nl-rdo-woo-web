<?php

declare(strict_types=1);

namespace App\Form\Inquiry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<InquiryLinkDossierFormType>
 */
class InquiryLinkDossierFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('map', TextType::class, [
                'label' => 'Zaaknummer(s)',
                'attr' => [
                    'class' => 'w-2/3',
                    'placeholder' => '100-2465, 102-2384',
                ],
                'help' => 'Separate multiple case numbers with commas',
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Enter a case number or multiple case numbers, separate multiple case numbers with commas']),
                ],
            ])
            ->add('dossiers', ChoiceType::class, [
                'label' => 'Published decisions',
                'help' => '',
                'multiple' => true,
                'expanded' => false,
                'choice_loader' => $options['choice_loader'],
                'row_attr' => [
                    'class' => 'js:hidden',
                ],
                'attr' => [
                    'placeholder' => 'Nothing chosen yet',
                    'class' => 'w-full js-select-dossiers-fallback',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(['message' => 'Choose from the selection of published decisions']),
                ],
            ])
            ->add('link', SubmitType::class, [
                'label' => 'Attach',
                'attr' => [
                    'data-first-button' => true,
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => 'Cancel',
                'attr' => [
                    'class' => 'bhr-button--secondary',
                    'data-last-button' => true,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('choice_loader');

        $resolver->setDefaults([
            'choices' => [],
        ]);
    }
}
