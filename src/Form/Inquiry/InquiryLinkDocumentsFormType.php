<?php

declare(strict_types=1);

namespace App\Form\Inquiry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Required;

/**
 * @template-extends AbstractType<InquiryLinkDocumentsFormType>
 */
class InquiryLinkDocumentsFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('upload', FileType::class, [
                'label' => 'admin.dossiers.inquiries.link_documents',
                'help' => 'admin.dossiers.inquiries.link_documents_help',
                'help_html' => true,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10024k',
                        'mimeTypesMessage' => 'Please upload a valid spreadsheet',
                    ]),
                    new NotBlank(),
                ],
            ])
            ->add('prefix', ChoiceType::class, [
                'label' => 'admin.dossiers.decision_prefix', // @codingStandardsIgnoreStart
                'attr' => [
                    'class' => 'w-9/12',
                ],
                // @codingStandardsIgnoreStart
                'help' => 'admin.dossiers.decision_prefix_help', // @codingStandardsIgnoreEnds
                'choice_loader' => $options['choice_loader'],
                'placeholder' => 'admin.global.dossiers.prefix_placeholder',
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Required(),
                ],
            ])
            ->add('link', SubmitType::class, [
                'label' => 'global.attach',
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
        ]);
    }
}
