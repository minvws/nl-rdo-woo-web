<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierDispatcher;

readonly class DeleteDossierAdminAction implements DossierAdminActionInterface
{
    public function __construct(
        private DossierDispatcher $dossierDispatcher,
    ) {
    }

    public function getAdminAction(): DossierAdminAction
    {
        return DossierAdminAction::DELETE;
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return true;
    }

    public function execute(AbstractDossier $dossier): void
    {
        $this->dossierDispatcher->dispatchDeleteDossierCommand(
            dossierId: $dossier->getId(),
            overrideWorkflow: true,
        );
    }

    public function needsConfirmation(): bool
    {
        return true;
    }
}
