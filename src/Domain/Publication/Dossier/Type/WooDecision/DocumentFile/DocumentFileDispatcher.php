<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile;

use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUpdatesCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileSetUploadsCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUpdateCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Command\ProcessDocumentFileUploadCommand;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileSet;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpdate;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Entity\DocumentFileUpload;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class DocumentFileDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchProcessDocumentFileSetUploadsCommand(DocumentFileSet $documentFileSet): void
    {
        $this->messageBus->dispatch(
            new ProcessDocumentFileSetUploadsCommand($documentFileSet->getId()),
        );
    }

    public function dispatchProcessDocumentFileUploadCommand(DocumentFileUpload $upload): void
    {
        $this->messageBus->dispatch(
            new ProcessDocumentFileUploadCommand($upload->getId()),
        );
    }

    public function dispatchProcessDocumentFileSetUpdatesCommand(DocumentFileSet $documentFileSet): void
    {
        $this->messageBus->dispatch(
            new ProcessDocumentFileSetUpdatesCommand($documentFileSet->getId()),
        );
    }

    public function dispatchProcessDocumentFileUpdateCommand(DocumentFileUpdate $update): void
    {
        $this->messageBus->dispatch(
            new ProcessDocumentFileUpdateCommand($update->getId()),
        );
    }

    public function dispatchDocumentFileSetProcessedEvent(DocumentFileSet $documentFileSet): void
    {
        $this->messageBus->dispatch(
            DocumentFileSetProcessedEvent::forDocumentFileSet($documentFileSet),
        );
    }
}
