<?php

declare(strict_types=1);

namespace Shared\Form\Inquiry;

use Shared\Domain\Upload\FileType\FileType as FileTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<InquiryLinkDocumentsFormType>
 */
class InquiryLinkDocumentsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('upload', FileType::class, [
                'label' => 'admin.dossiers.inquiries.link_documents',
                'help' => 'admin.dossiers.inquiries.link_documents_help',
                'help_html' => true,
                'required' => true,
                'constraints' => [
                    new File(maxSize: '10024k', mimeTypes: FileTypeEnum::XLS->getMimeTypes(), mimeTypesMessage: 'Please upload a valid spreadsheet'),
                    new NotBlank(),
                ],
                'attr' => [
                    'accept' => FileTypeEnum::XLS->getMimeTypes(),
                    'typeName' => FileTypeEnum::XLS->getTypeName(),
                ],
            ])
            ->add('link', SubmitType::class, [
                'label' => 'global.attach',
                'attr' => [
                    'data-first-button' => true,
                ],
            ])
            ->add('cancel', SubmitType::class, [
                'label' => 'admin.inquiries.back_to_overview',
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
                    'data-last-button' => true,
                ],
            ]);
    }
}
