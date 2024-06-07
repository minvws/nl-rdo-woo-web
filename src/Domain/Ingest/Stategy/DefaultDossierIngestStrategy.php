<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Stategy;

use App\Domain\Ingest\DossierIngestStrategyInterface;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Search\Index\IndexDossierMessage;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DefaultDossierIngestStrategy implements DossierIngestStrategyInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh): void
    {
        // TODO: for now this only indexes the main document
        // Ingest for documents/attachments to be added later (based on hasMainDocument and hasAttachments interfaces).
        // The refresh param will become relevant once files are also ingested.
        unset($refresh);

        $this->messageBus->dispatch(
            IndexDossierMessage::forDossier($dossier)
        );
    }
}
