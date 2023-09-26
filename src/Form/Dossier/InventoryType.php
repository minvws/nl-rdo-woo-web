<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<InventoryType>
 */
class InventoryType extends AbstractType
{
    protected const SPREADSHEET_MIMETYPES = [
        'application/xls',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('inventory', FileType::class, [
                'label' => 'Inventaris',
                'required' => true,
                'help' => 'De inventaris van het dossier',
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => self::SPREADSHEET_MIMETYPES,
                        'mimeTypesMessage' => 'Please upload a valid spreadsheet',
                    ]),
                    new NotBlank(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Upload',
            ]);
    }
}
