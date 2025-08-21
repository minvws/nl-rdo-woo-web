<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Enum\AttachmentWithdrawReason;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<WithdrawAttachmentFormType>
 */
class WithdrawAttachmentFormType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reason', EnumType::class, [
                'label' => 'admin.attachment.withdraw.reason',
                'required' => true,
                'class' => AttachmentWithdrawReason::class,
                'expanded' => true,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('explanation', TextareaType::class, [
                'label' => 'admin.attachment.withdraw.explanation',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(
                        min: AbstractAttachment::WITHDRAW_EXPLANATION_MIN_LENGTH,
                        max: AbstractAttachment::WITHDRAW_EXPLANATION_MAX_LENGTH,
                    ),
                ],
                'attr' => [
                    'class' => 'w-full',
                    'rows' => 5,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.attachment.withdraw.submit',
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
