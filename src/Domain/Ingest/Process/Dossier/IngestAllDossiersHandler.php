<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier;

use App\Domain\Ingest\IngestDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class IngestAllDossiersHandler
{
    public function __construct(
        private IngestDispatcher $ingestDispatcher,
    ) {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function __invoke(IngestAllDossiersCommand $message): void
    {
        $this->ingestDispatcher->dispatchIngestDossierCommandForAllDossiers();
    }
}
