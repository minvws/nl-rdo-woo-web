<?php

declare(strict_types=1);

namespace Shared\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class DossierChangedCollectionPublisher
{
    public function __construct(
        private DossierChangedCollection $dossierChangedCollection,
        private DossierRepository $dossierRepository,
        private MessageBusInterface $messageBus,
    ) {
    }

    #[AsEventListener(event: KernelEvents::TERMINATE, priority: EventPriorities::PRE_RESPOND)]
    #[AsEventListener(event: WorkerMessageHandledEvent::class)]
    public function processDossierChangedCollection(): void
    {
        foreach ($this->dossierChangedCollection->claim() as $dossierId) {
            $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

            $this->messageBus->dispatch(new UpdateDossierPublicationCommand($dossier));

            $this->dossierChangedCollection->removeDossierIdFromCollection($dossierId);
        }

        $this->dossierChangedCollection->reset();
    }
}
