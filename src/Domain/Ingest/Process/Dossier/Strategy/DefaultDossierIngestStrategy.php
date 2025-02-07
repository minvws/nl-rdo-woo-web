<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier\Strategy;

use App\Domain\Ingest\Process\Dossier\DossierIngestStrategyInterface;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Search\SearchDispatcher;

readonly class DefaultDossierIngestStrategy implements DossierIngestStrategyInterface
{
    public function __construct(
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh): void
    {
        $this->searchDispatcher->dispatchIndexDossierCommand($dossier->getId(), $refresh);

        if ($dossier instanceof EntityWithMainDocument && $dossier->getMainDocument() !== null) {
            $this->searchDispatcher->dispatchIndexMainDocumentCommand($dossier->getMainDocument()->getId());
        }

        if ($dossier instanceof EntityWithAttachments) {
            foreach ($dossier->getAttachments() as $attachment) {
                $this->searchDispatcher->dispatchIndexAttachmentCommand($attachment->getId());
            }
        }
    }
}
