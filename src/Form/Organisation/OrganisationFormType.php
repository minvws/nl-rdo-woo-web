<?php

declare(strict_types=1);

namespace App\Form\Organisation;

use App\Entity\Department;
use App\Entity\DocumentPrefix;
use App\Entity\Organisation;
use App\Form\DocumentPrefixType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @template-extends AbstractType<OrganisationFormType>
 */
class OrganisationFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Organisation|null $organisation */
        $organisation = $builder->getData();
        if ($organisation === null) {
            // This ensures one empty field is shown when first loading the form
            $documentPrefixes = [new DocumentPrefix()];
        } else {
            $documentPrefixes = $organisation->getDocumentPrefixes();
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Naam organisatie',
                'help' => 'Bijvoorbeeld Ministerie van VWS - Directie Wetgeving en Juridische Zaken',
                'attr' => [
                    'class' => 'w-full',
                ],
                'help_attr' => [
                    'class' => 'text-sm mb-2',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3, 'max' => 255]),
                ],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'Department',
                'attr' => [
                    'after' => 'Organisation',
                    'class' => 'min-w-full',
                ],
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name_and_short',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.name', 'ASC');
                },
                'placeholder' => 'admin.organisation.select',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('documentPrefixes', CollectionType::class, [
                'entry_type' => DocumentPrefixType::class,
                'entry_options' => [
                    'attr' => [
                        'class' => 'w-full',
                        'readonly' => $organisation !== null,
                    ],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'prototype_options' => [
                    'attr' => [
                        'readonly' => false,
                        'class' => 'w-full',
                    ],
                ],
                'by_reference' => false,
                'data' => $documentPrefixes,
                'constraints' => [
                    new NotBlank(),
                    new Callback([$this, 'validatePrefixes']),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }

    /**
     * This validates if all given prefixes in the collection are unique (not adding the same value twice).
     *
     * @param DocumentPrefix[] $documentPrefixes
     */
    public function validatePrefixes(iterable $documentPrefixes, ExecutionContextInterface $context): void
    {
        $prefixes = [];
        foreach ($documentPrefixes as $documentPrefix) {
            try {
                $prefix = $documentPrefix->getPrefix();
            } catch (\Throwable) {
                // If the field value is empty the 'getPrefix' call can trigger an uninitialized var error
                continue;
            }

            if (in_array($prefix, $prefixes, true)) {
                $context
                    ->buildViolation('Er mogen geen dubbele prefixes ingevoerd worden')
                    ->addViolation();
            } else {
                $prefixes[] = $prefix;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organisation::class,
        ]);
    }
}
