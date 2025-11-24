<?php

declare(strict_types=1);

namespace Shared\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<LandingPageType>
 */
class ContentPageType extends AbstractType
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'admin.content-page.title',
                'required' => true,
                'help' => 'admin.content-page.title_help',
                'empty_data' => '',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'admin.content-page.content',
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
