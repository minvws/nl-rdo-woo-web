<?php

declare(strict_types=1);

namespace App\Form\Organisation;

use App\Entity\Department;
use App\Entity\Organisation;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @template-extends AbstractType<OrganisationUpdateFormType>
 */
class OrganisationUpdateFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'help' => 'Minimaal 3 en maximaal 255 karakters',
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 3, 'max' => 255]),
                ],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'label' => 'Organisation',
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name_and_short',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('d')
                        ->orderBy('d.name', 'ASC');
                },
                'placeholder' => 'Choose an organisation',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organisation::class,
        ]);
    }
}
