<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<DocumentUploadType>
 */
class DocumentUploadType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('upload', FileType::class, [
                'mapped' => false,
                'multiple' => true,
                'required' => true,
//                'constraints' => [
//                    new File([
//                        'maxSize' => '500M',
//                        'mimeTypes' => [
//                            'application/pdf',
//                            'application/x-pdf',
//                            'application/zip',
//                            'application/x-zip-compressed',
//                        ],
//                        'mimeTypesMessage' => 'Please upload a valid PDF document or ZIP archive',
//                    ])
//                ]
            ])
        ;
    }
}
