<?php

declare(strict_types=1);

namespace Shared\Domain\Ingest\Process\Dossier\Strategy;

use Shared\Domain\Ingest\Process\Dossier\DossierIngestStrategyInterface;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Search\SearchDispatcher;

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
                if ($attachment->isWithdrawn()) {
                    continue;
                }

                $this->searchDispatcher->dispatchIndexAttachmentCommand($attachment->getId());
            }
        }
    }
}
