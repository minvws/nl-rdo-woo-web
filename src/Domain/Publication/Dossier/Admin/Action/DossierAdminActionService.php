<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\AbstractDossier;

readonly class DossierAdminActionService
{
    /**
     * @param iterable<DossierAdminActionInterface> $actions
     */
    public function __construct(private iterable $actions)
    {
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
}
