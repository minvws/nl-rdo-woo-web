<?php

declare(strict_types=1);

namespace App\Form\Document;

use App\Entity\WithdrawReason;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<WithdrawFormType>
 */
class WithdrawFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', EnumType::class, [
                'label' => 'Reden',
                'required' => true,
                'class' => WithdrawReason::class,
                'expanded' => true,
                'placeholder' => 'Choose an option',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('explanation', TextareaType::class, [
                'label' => 'Toelichting',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'w-full',
                    'rows' => 5,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Intrekken',
                'attr' => [
                    'class' => 'bhr-button--primary',
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
        $resolver->setDefaults([]);
    }
}
