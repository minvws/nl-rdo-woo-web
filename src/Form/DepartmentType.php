<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<DepartmentType>
 */
class DepartmentType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'admin.department.name',
                'required' => true,
                'help' => 'admin.department.name_help',
                'empty_data' => '',
            ])
            ->add('shortTag', TextType::class, [
                'label' => 'admin.department.abbreviation',
                'required' => true,
                'help' => 'admin.department.abbreviation_help',
                'empty_data' => '',
            ])
            ->add('slug', TextType::class, [
                'label' => 'admin.department.slug',
                'required' => true,
                'help' => 'admin.department.slug_help',
                'empty_data' => '',
            ])
            ->add('public', CheckboxType::class, [
                'label' => 'admin.department.public',
                'help' => 'admin.department.public_help',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }
}
