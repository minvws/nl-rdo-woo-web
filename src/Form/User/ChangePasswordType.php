<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Validator\CommonList;
use App\Validator\SimilarityEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @template-extends AbstractType<ChangePasswordType>
 */
class ChangePasswordType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'required' => true,
                'constraints' => [
                    new UserPassword(),
                ],
                'mapped' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options' => ['label' => 'New Password'],
                'second_options' => ['label' => 'Repeat Password'],
                'attr' => ['autocomplete' => 'off'],
                'constraints' => [
                    new Length([
                        'min' => 14,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/(?!^\d+$)^.+$/',
                        'message' => 'Your password must not contain only digits.',
                    ]),
                    new CommonList(),           // Not a common password
                    new SimilarityEmail(),      // Not similar to user's email address
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Wachtwoord aanpassen',
            ])
        ;
    }
}
