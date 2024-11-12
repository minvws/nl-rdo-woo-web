<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Search\Index\DeleteElasticDocumentCommand;
use App\Domain\Search\Index\Dossier\IndexDossierCommand;
use App\Domain\Search\Index\SubType\IndexAttachmentCommand;
use App\Domain\Search\Index\SubType\IndexMainDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class SearchDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchDeleteElasticDocumentCommand(string $id): void
    {
        $this->messageBus->dispatch(
            new DeleteElasticDocumentCommand($id),
        );
    }

    public function dispatchIndexAttachmentCommand(Uuid $uuid): void
    {
        $this->messageBus->dispatch(
            new IndexAttachmentCommand($uuid),
        );
    }

    public function dispatchIndexMainDocumentCommand(Uuid $uuid): void
    {
        $this->messageBus->dispatch(
            new IndexMainDocumentCommand($uuid),
        );
    }

    public function dispatchIndexDossierCommand(Uuid $uuid, bool $refresh = true): void
    {
        $this->messageBus->dispatch(
            new IndexDossierCommand($uuid, $refresh),
        );
    }
}
