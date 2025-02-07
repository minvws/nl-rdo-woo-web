<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\SearchDispatcher;

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
}
