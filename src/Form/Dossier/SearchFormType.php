<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Domain\Publication\Dossier\Admin\DossierFilterParameters;
use App\Domain\Publication\Dossier\Admin\DossierListingService;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Entity\Department;
use App\Form\Dossier\WooDecision\DocumentUploadType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<DocumentUploadType>
 */
class SearchFormType extends AbstractType
{
    public function __construct(
        private readonly DossierListingService $listingService,
    ) {
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statuses', EnumType::class, [
                'class' => DossierStatus::class,
                'required' => false,
                'choices' => DossierStatus::filterCases(),
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('departments', EntityType::class, [
                'class' => Department::class,
                'choice_label' => 'name',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
            ]);

        $availableDossierTypes = $this->listingService->getAvailableTypes();
        if (count($availableDossierTypes) > 1) {
            $builder->add('types', EnumType::class, [
                'class' => DossierType::class,
                'required' => false,
                'choices' => $availableDossierTypes,
                'expanded' => true,
                'multiple' => true,
            ]);
        }

        $builder->add('submit', SubmitType::class)
            ->setMethod('GET')
            ->getForm();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DossierFilterParameters::class,
            'csrf_protection' => false,
        ]);
    }
}
