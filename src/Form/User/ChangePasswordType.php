<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Validator\CommonList;
use App\Validator\NotTheSamePassword;
use App\Validator\SimilarityEmail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @template-extends AbstractType<ChangePasswordType>
 */
class ChangePasswordType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'required' => true,
                'constraints' => [
                    new UserPassword(),
                ],
                'label' => 'admin.user.password_current',
                'mapped' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'admin.user.password.validation.must_match',
                'required' => true,
                'first_options' => [
                    'label' => 'admin.user.password_new',
                    'attr' => [
                        'aria-describedby' => 'password-instructions',
                    ],
                ],
                'second_options' => ['label' => 'admin.user.password_repeat'],
                'attr' => [
                    'autocomplete' => 'off',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length([
                        'min' => 14,
                        'minMessage' => 'admin.user.password.validation.minimal_chars',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/(?!^\d+$)^.+$/',
                        'message' => 'admin.user.password.validation.digits_only',
                    ]),
                    new NotTheSamePassword(),   // Not the same as the current password
                    new CommonList(),           // Not a common password
                    new SimilarityEmail(),      // Not similar to user's email address
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.user.password.edit_confirm',
            ])
        ;
    }
}
