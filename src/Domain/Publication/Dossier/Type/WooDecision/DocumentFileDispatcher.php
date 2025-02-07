<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileSetUpdatesCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileSetUploadsCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileUpdateCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Command\ProcessDocumentFileUploadCommand;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileSet;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpdate;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\DocumentFileUpload;
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
}
