<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<GovernmentOfficialType>
 */
class GovernmentOfficialType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Naam',
                'required' => true,
                'help' => 'Naam van bewindspersoon',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Opslaan',
            ])
        ;
    }
}
