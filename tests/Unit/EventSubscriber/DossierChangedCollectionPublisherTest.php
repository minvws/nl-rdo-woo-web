<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use Mockery;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\EventSubscriber\DossierChangedCollection;
use Shared\EventSubscriber\DossierChangedCollectionPublisher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\MessageBusInterface;

class DossierChangedCollectionPublisherTest extends UnitTestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                KernelEvents::TERMINATE => ['processDossierChangedCollection', EventPriorities::PRE_RESPOND],
                WorkerMessageHandledEvent::class => 'processDossierChangedCollection',
            ],
            DossierChangedCollectionPublisher::getSubscribedEvents(),
        );
    }

    public function testProcessesAndMarksEachDossierAsProcessed(): void
    {
        $wooDecision = new WooDecision();
        $collection = new DossierChangedCollection();
        $collection->addDossierId($wooDecision->getId());

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneByDossierId')
            ->once()
            ->with($wooDecision->getId())
            ->andReturn($wooDecision);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->once()
            ->with(Mockery::on(
                static fn (UpdateDossierPublicationCommand $command): bool => $command->dossier === $wooDecision,
            ));

        $publisher = new DossierChangedCollectionPublisher($collection, $dossierRepository, $messageBus);
        $publisher->processDossierChangedCollection();
    }

    public function testDoesNothingWhenCollectionIsEmpty(): void
    {
        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneByDossierId')->never();

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->never();

        $publisher = new DossierChangedCollectionPublisher(
            new DossierChangedCollection(),
            $dossierRepository,
            $messageBus,
        );
        $publisher->processDossierChangedCollection();
    }

    public function testSecondCallDoesNotReprocessAfterClaim(): void
    {
        $wooDecision = new WooDecision();
        $collection = new DossierChangedCollection();
        $collection->addDossierId($wooDecision->getId());

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneByDossierId')->once()->andReturn($wooDecision);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')->once();

        $publisher = new DossierChangedCollectionPublisher($collection, $dossierRepository, $messageBus);

        $publisher->processDossierChangedCollection();
        $publisher->processDossierChangedCollection();
    }
}
