<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DossierChangedCollectionPublisher implements EventSubscriberInterface
{
    public function __construct(
        private DossierChangedCollection $dossierChangedCollection,
        private DossierRepository $dossierRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => ['handleDossierChangedCollection', EventPriorities::PRE_RESPOND],
        ];
    }

    public function handleDossierChangedCollection(): void
    {
        foreach ($this->dossierChangedCollection as $dossierId) {
            $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

            $this->messageBus->dispatch(new UpdateDossierPublicationCommand($dossier));
        }
    }
}
