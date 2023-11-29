<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

/**
 * @template-extends AbstractType<DocumentUploadType>
 */
class DocumentUploadType extends AbstractType
{
    protected const VALID_MIMETYPES = [
        'application/pdf',
        'application/x-pdf',
        'application/zip',
        'application/x-zip-compressed',
    ];

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
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '4096M',
                            'mimeTypes' => self::VALID_MIMETYPES,
                            'mimeTypesMessage' => 'Please upload a valid PDF document or ZIP archive',
                        ]),
                    ]),
                ],
                'attr' => [
                    'accept' => self::VALID_MIMETYPES,
                ],
            ])
        ;
    }
}
