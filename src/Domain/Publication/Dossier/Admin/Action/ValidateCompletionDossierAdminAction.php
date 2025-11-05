<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Service\DossierService;

readonly class ValidateCompletionDossierAdminAction implements DossierAdminActionInterface
{
    public function __construct(
        private DossierService $dossierService,
    ) {
    }

    public function getAdminAction(): DossierAdminAction
    {
        return DossierAdminAction::VALIDATE_COMPLETION;
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return true;
    }

    public function execute(AbstractDossier $dossier): void
    {
        $this->dossierService->validateCompletion($dossier);
    }

    public function needsConfirmation(): bool
    {
        return false;
    }
}
