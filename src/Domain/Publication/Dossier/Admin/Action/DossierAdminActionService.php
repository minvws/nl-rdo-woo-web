<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin\Action;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

readonly class DossierAdminActionService
{
    /**
     * @param iterable<DossierAdminActionInterface> $actions
     */
    public function __construct(
        #[AutowireIterator('woo_platform.publication.dossier.admin.action')]
        private iterable $actions,
    ) {
    }

    /**
     * @return array<array-key, DossierAdminAction>
     */
    public function getAvailableAdminActions(AbstractDossier $dossier): array
    {
        $actions = [];
        foreach ($this->actions as $action) {
            if ($action->supports($dossier)) {
                $actions[] = $action->getAdminAction();
            }
        }

        return $actions;
    }

    public function execute(AbstractDossier $dossier, DossierAdminAction $adminAction): void
    {
        foreach ($this->actions as $action) {
            if ($action->getAdminAction() === $adminAction && $action->supports($dossier)) {
                $action->execute($dossier);

                return;
            }
        }

        throw DossierAdminActionException::forActionNotAvailable($dossier, $adminAction);
    }

    public function needsConfirmation(DossierAdminAction $adminAction): bool
    {
        foreach ($this->actions as $action) {
            if ($action->getAdminAction() === $adminAction) {
                return $action->needsConfirmation();
            }
        }

        return false;
    }
}
