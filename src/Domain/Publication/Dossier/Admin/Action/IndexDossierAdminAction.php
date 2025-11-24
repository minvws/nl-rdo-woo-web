<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin\Action;

use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Search\SearchDispatcher;

readonly class IndexDossierAdminAction implements DossierAdminActionInterface
{
    public function __construct(
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function getAdminAction(): DossierAdminAction
    {
        return DossierAdminAction::INDEX;
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return true;
    }

    public function execute(AbstractDossier $dossier): void
    {
        $this->searchDispatcher->dispatchIndexDossierCommand($dossier->getId());
    }

    public function needsConfirmation(): bool
    {
        return false;
    }
}
