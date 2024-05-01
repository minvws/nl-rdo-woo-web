<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Covenant;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Search\Index\IndexDossierMessage;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class CovenantIngester
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function ingest(Covenant $dossier, bool $refresh): void
    {
        // TODO: for now this only indexes the main covenant document, ingest for document and attachments to be added
        // later. The refresh param will become relevant once files are also ingested.
        unset($refresh);

        $this->messageBus->dispatch(
            IndexDossierMessage::forDossier($dossier)
        );
    }
}
