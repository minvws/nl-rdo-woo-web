<?php

declare(strict_types=1);

namespace Shared\Form\Dossier;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionService;
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
