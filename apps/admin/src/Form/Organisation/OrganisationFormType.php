<?php

declare(strict_types=1);

namespace Admin\Form\Organisation;

use Shared\Domain\Department\Department;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<OrganisationFormType>
 */
class OrganisationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'admin.organisation.name',
                'help' => 'admin.organisation.name_help',
                'attr' => [
                    'class' => 'w-full',
                    'data-e2e-name' => 'organisation-name-input',
                ],
                'help_attr' => [
                    'class' => 'text-sm mb-2',
                ],
                'empty_data' => '',
            ]);

        if ($options['prefix_editable']) {
            $builder->add('prefix', OrganisationPrefixType::class, [
                'label' => 'admin.organisation.prefix',
                'help' => 'admin.organisation.prefix_help',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'w-full',
                    'data-e2e-name' => 'organisation-prefix-input',
                ],
            ]);
        }

        $builder
            ->add('departments', EntityType::class, [
                'class' => Department::class,
                'attr' => [
                    'class' => 'min-w-full',
                ],
                'required' => true,
                'multiple' => true,
                'choice_label' => 'name',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'global.save',
                'attr' => [
                    'data-e2e-name' => 'organisation-form-submit',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrganisationFormData::class,
            'prefix_editable' => true,
        ]);
        $resolver->setAllowedTypes('prefix_editable', 'bool');
    }
}
