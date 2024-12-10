<?php

declare(strict_types=1);

namespace App\Form\Document;

use App\Service\Uploader\UploadGroupId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<ReplaceFormType>
 */
class ReplaceFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mimeTypes = UploadGroupId::WOO_DECISION_DOCUMENTS->getMimeTypes();

        $builder
            ->add('document', FileType::class, [
                'label' => 'Document',
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => $mimeTypes,
                        'mimeTypesMessage' => 'Gebruik een document van het juiste type',
                    ]),
                    new NotBlank(),
                ],
                'attr' => [
                    'accept' => $mimeTypes,
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Vervang document',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
