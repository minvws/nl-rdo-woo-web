<?php

declare(strict_types=1);

namespace App\Form\Dossier\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Upload\FileType\FileType as FileTypeEnum;
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
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var WooDecision $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('inventory', FileType::class, [
                'label' => false,
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '10024k',
                        'mimeTypes' => FileTypeEnum::XLS->getMimeTypes(),
                        'mimeTypesMessage' => 'Please upload a valid spreadsheet',
                    ]),
                    new NotBlank(),
                ],
                'attr' => [
                    'accept' => FileTypeEnum::XLS->getMimeTypes(),
                    'typeName' => FileTypeEnum::XLS->getTypeName(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.inventory.submit_label',
            ]);

        if ($dossier->getProductionReport() !== null) {
            $builder->add('cancel', SubmitType::class, [
                'label' => 'global.cancel',
                'attr' => [
                    'class' => 'bhr-btn-bordered-primary',
                ],
            ]);
        }
    }
}
