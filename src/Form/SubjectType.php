<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<DepartmentType>
 */
class SubjectType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'admin.subject.name',
                'required' => true,
                'help' => 'admin.subject.name_help',
                'empty_data' => '',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }
}
