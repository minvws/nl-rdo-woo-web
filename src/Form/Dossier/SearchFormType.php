<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Publication\Dossier\Admin\DossierFilterParameters;
use Shared\Domain\Publication\Dossier\Admin\DossierListingService;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<SearchFormType>
 */
class SearchFormType extends AbstractType
{
    public function __construct(
        private readonly DossierListingService $listingService,
        private readonly AuthorizationMatrix $authorizationMatrix,
        private readonly DepartmentRepository $departmentRepository,
    ) {
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
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
            ]);

        $builder
            ->add('departments', EntityType::class, [
                'class' => Department::class,
                'choice_label' => 'name',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'choices' => $this->departmentRepository->getOrganisationDepartmentsSortedByName(
                    $this->authorizationMatrix->getActiveOrganisation()
                ),
            ]);

        $availableDossierTypes = $this->listingService->getAvailableTypesOrderedByName();
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
