<?php

declare(strict_types=1);

namespace App\Form;

use App\Domain\Publication\Dossier\DocumentPrefix;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @template-extends AbstractType<DocumentPrefixType>
 */
class DocumentPrefixType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('prefix', TextType::class, [
                'attr' => [
                    'no_container' => true,
                    'class' => 'w-full',
                ],
                'label' => 'Prefix',
                'label_attr' => [
                    'class' => 'sr-only',
                ],
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 5, 'max' => 30]),
                    new Regex('/^[0-9a-zA-Z-]+$/', 'alpha_numeric_dash_only'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentPrefix::class,
        ]);
    }
}
