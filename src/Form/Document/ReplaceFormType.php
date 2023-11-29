<?php

declare(strict_types=1);

namespace App\Form\Document;

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
    protected const DOCUMENT_MIMETYPES = [
        'application/pdf',
    ];

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('document', FileType::class, [
                'label' => 'Document',
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => self::DOCUMENT_MIMETYPES,
                        'mimeTypesMessage' => 'Gebruik een document van het type PDF',
                    ]),
                    new NotBlank(),
                ],
                'attr' => [
                    'accept' => self::DOCUMENT_MIMETYPES,
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
