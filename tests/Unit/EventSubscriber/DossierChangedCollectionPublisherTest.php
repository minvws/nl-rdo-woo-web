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
use Symfony\Component\Messenger\MessageBusInterface;

class DossierChangedCollectionPublisherTest extends UnitTestCase
{
    public function testGetSubscribedEvents(): void
    {
        $expectedSubscribedEvents = [
            KernelEvents::TERMINATE => ['handleDossierChangedCollection', EventPriorities::PRE_RESPOND],
        ];
        $subscribedEvents = DossierChangedCollectionPublisher::getSubscribedEvents();

        self::assertEquals($expectedSubscribedEvents, $subscribedEvents);
    }

    public function testHandleDossierChangedCollection(): void
    {
        $wooDecision = new WooDecision();

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneByDossierId')
            ->with($wooDecision->getId())
            ->andReturn($wooDecision);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(static function (UpdateDossierPublicationCommand $updateDossierPublicationCommand) use ($wooDecision): bool {
                return $updateDossierPublicationCommand->dossier === $wooDecision;
            }));

        $dossierChangedCollectionPublisher = new DossierChangedCollectionPublisher(
            new DossierChangedCollection([$wooDecision->getId()]),
            $dossierRepository,
            $messageBus,
        );
        $dossierChangedCollectionPublisher->handleDossierChangedCollection();
    }
}
