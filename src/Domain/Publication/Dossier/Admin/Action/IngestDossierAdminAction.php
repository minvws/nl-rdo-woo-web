<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Admin\Action;

use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\AbstractDossier;

readonly class IngestDossierAdminAction implements DossierAdminActionInterface
{
    public function __construct(
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    public function getAdminAction(): DossierAdminAction
    {
        return DossierAdminAction::INGEST;
    }

    public function supports(AbstractDossier $dossier): bool
    {
        return true;
    }

    public function execute(AbstractDossier $dossier): void
    {
        $this->ingestDispatcher->dispatchIngestDossierCommand($dossier);
    }

    public function needsConfirmation(): bool
    {
        return false;
    }
}
