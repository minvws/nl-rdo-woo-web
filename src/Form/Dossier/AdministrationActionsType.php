<?php

declare(strict_types=1);

namespace App\Form\Dossier;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminActionService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @template-extends AbstractType<AdministrationActionsType>
 */
class AdministrationActionsType extends AbstractType
{
    public function __construct(
        private readonly DossierAdminActionService $adminActionService,
    ) {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AbstractDossier $dossier */
        $dossier = $builder->getData();

        $builder
            ->add('action', EnumType::class, [
                'label' => 'admin.dossiers.action.form.action',
                'class' => DossierAdminAction::class,
                'choices' => $this->adminActionService->getAvailableAdminActions($dossier),
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'admin.dossiers.action.form.submit',
            ]);
    }
}
