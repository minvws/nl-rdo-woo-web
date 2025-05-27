<?php

declare(strict_types=1);

namespace App\Form\Document;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<WithdrawDocumentFormType>
 */
class WithdrawDocumentFormType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', EnumType::class, [
                'label' => 'Reden',
                'required' => true,
                'class' => DocumentWithdrawReason::class,
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
                    'class' => 'bhr-btn-filled-primary',
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => 'global.cancel',
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
                    'data-last-button' => true,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
