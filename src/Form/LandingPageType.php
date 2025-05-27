<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<LandingPageType>
 */
class LandingPageType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('landingpage_title', TextType::class, [
                'label' => 'admin.department.landing_page.title',
                'required' => true,
                'help' => 'admin.department.landing_page.title_help',
                'empty_data' => '',
            ])
            ->add('landingpage_description', TextareaType::class, [
                'label' => 'admin.department.landing_page.description',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'data-is-markdown' => 'true',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'global.save',
            ])
        ;
    }
}
