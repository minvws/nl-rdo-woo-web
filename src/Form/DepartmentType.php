<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

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
            ->add('short_tag', TextType::class, [
                'label' => 'Short name',
                'required' => true,
                'help' => 'Short name of the department',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 10]),
                ],
            ])
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'help' => 'Name of the department',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 2, 'max' => 100]),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }
}
