<?php

declare(strict_types=1);

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<ResetCredentialsFormType>
 */
class ResetCredentialsFormType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reset_pw', HiddenType::class, [
                'attr' => [
                    'value' => 1,
                ],
            ])
            ->add('reset_2fa', HiddenType::class, [
                'attr' => [
                    'value' => 1,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.user.reset',
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary mt-6',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
