<?php

declare(strict_types=1);

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<ResetCredentialsFormType>
 */
class ResetCredentialsFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reset_pw', CheckboxType::class, [
                'label' => 'Reset password',
                'required' => false,
                'attr' => [
                    'help' => 'Reset the password of this user to a random value',
                ],
            ])
            ->add('reset_2fa', CheckboxType::class, [
                'label' => 'Reset two factor authentication',
                'required' => false,
                'attr' => [
                    'help' => 'Reset the two factor authentication of this user to a random value',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Reset credentials',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
