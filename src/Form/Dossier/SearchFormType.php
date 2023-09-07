<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Entity\Department;
use App\Entity\Dossier;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<DocumentUploadType>
 */
class SearchFormType extends AbstractType
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    Dossier::STATUS_CONCEPT => Dossier::STATUS_CONCEPT,
                    Dossier::STATUS_COMPLETED => Dossier::STATUS_COMPLETED,
                    Dossier::STATUS_PREVIEW => Dossier::STATUS_PREVIEW,
                    Dossier::STATUS_PUBLISHED => Dossier::STATUS_PUBLISHED,
                    Dossier::STATUS_RETRACTED => Dossier::STATUS_RETRACTED,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'choice_label' => 'name',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ])

            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'icon icon-search',
                ],
            ])
            ->setMethod('GET')
            ->getForm();
    }
}
