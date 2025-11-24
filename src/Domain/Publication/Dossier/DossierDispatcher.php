<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\Command\CreateDossierCommand;
use Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Service\Security\AuditUserDetails;
use Shared\Service\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class DossierDispatcher
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private Security $security,
    ) {
    }

    public function dispatchCreateDossierCommand(AbstractDossier $dossier): void
    {
        $this->messageBus->dispatch(
            new CreateDossierCommand($dossier),
        );
    }

    public function dispatchUpdateDossierCommand(AbstractDossier $dossier): void
    {
        $this->dispatchUpdateDossierDetailsCommand($dossier);
        $this->dispatchUpdateDossierContentCommand($dossier);
        $this->dispatchUpdateDossierPublicationCommand($dossier);
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

    public function dispatchDeleteDossierCommand(Uuid $dossierId, bool $overrideWorkflow = false): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        Assert::isInstanceOf($user, User::class);

        $this->messageBus->dispatch(
            new DeleteDossierCommand(
                $dossierId,
                new AuditUserDetails(
                    $user->getUserIdentifier(),
                    $user->getName(),
                    $user->getRoles(),
                    $user->getEmail(),
                ),
                $overrideWorkflow,
            ),
        );
    }
}
