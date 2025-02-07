<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\Command\CreateDossierCommand;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use App\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

readonly class DossierDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatchCreateDossierCommand(AbstractDossier $dossier): void
    {
        $this->messageBus->dispatch(
            new CreateDossierCommand($dossier),
        );
    }

    public function dispatchUpdateDossierDetailsCommand(AbstractDossier $dossier): void
    {
        $this->messageBus->dispatch(
            new UpdateDossierDetailsCommand($dossier),
        );
    }

    public function dispatchUpdateDossierContentCommand(AbstractDossier $dossier): void
    {
        $this->messageBus->dispatch(
            new UpdateDossierContentCommand($dossier),
        );
    }

    public function dispatchUpdateDossierPublicationCommand(AbstractDossier $dossier): void
    {
        $this->messageBus->dispatch(
            new UpdateDossierPublicationCommand($dossier),
        );
    }

    public function dispatchDeleteDossierCommand(Uuid $id): void
    {
        $this->messageBus->dispatch(
            new DeleteDossierCommand($id),
        );
    }
}
