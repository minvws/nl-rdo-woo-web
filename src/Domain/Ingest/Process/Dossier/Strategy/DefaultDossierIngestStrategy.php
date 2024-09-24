<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\Dossier\Strategy;

use App\Domain\Ingest\Process\Dossier\DossierIngestStrategyInterface;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Search\Index\Dossier\IndexDossierCommand;
use App\Domain\Search\Index\SubType\IndexAttachmentCommand;
use App\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DefaultDossierIngestStrategy implements DossierIngestStrategyInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function ingest(AbstractDossier $dossier, bool $refresh): void
    {
        $this->messageBus->dispatch(
            IndexDossierCommand::forDossier($dossier, $refresh)
        );

        if ($dossier instanceof EntityWithMainDocument && $dossier->getDocument() !== null) {
            $this->messageBus->dispatch(
                IndexMainDocumentCommand::forMainDocument($dossier->getDocument())
            );
        }

        if ($dossier instanceof EntityWithAttachments) {
            foreach ($dossier->getAttachments() as $attachment) {
                $this->messageBus->dispatch(
                    IndexAttachmentCommand::forAttachment($attachment)
                );
            }
        }
    }
}
