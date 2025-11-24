<?php

declare(strict_types=1);

namespace Shared\Form\Dossier\WooDecision;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Upload\FileType\FileType as FileTypeEnum;
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

        $inventoryRequired = $dossier->isInventoryRequired();

        $inventoryConstraints = [
            new File([
                'maxSize' => '10024k',
                'mimeTypes' => FileTypeEnum::XLS->getMimeTypes(),
                'mimeTypesMessage' => 'Please upload a valid spreadsheet',
            ]),
        ];

        if ($inventoryRequired) {
            $inventoryConstraints[] = new NotBlank();
        }

        $builder
            ->add('inventory', FileType::class, [
                'label' => false,
                'required' => $inventoryRequired,
                'mapped' => false,
                'constraints' => $inventoryConstraints,
                'attr' => [
                    'accept' => FileTypeEnum::XLS->getMimeTypes(),
                    'typeName' => FileTypeEnum::XLS->getTypeName(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => $inventoryRequired ? 'admin.inventory.submit_label' : 'global.save_and_continue',
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
