<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Listener;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Event\DossierNumberChangedEvent;
use Shared\Domain\Publication\Dossier\Listener\DossierNumberChangedHandler;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DossierNumberChangedHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private DossierDispatcher&MockInterface $dossierDispatcher;

    private DossierNumberChangedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->dossierDispatcher = Mockery::mock(DossierDispatcher::class);

        $this->handler = new DossierNumberChangedHandler(
            $this->dossierRepository,
            $this->dossierDispatcher,
        );
    }

    public function testUpdateDossierForWooDecision(): void
    {
        $dossier = new WooDecision();
        $event = new DossierNumberChangedEvent($dossier->getId(), 'old-nr', 'new-nr', DossierStatus::PUBLISHED);

        $this->dossierRepository->expects('findOneByDossierId')
            ->with($event->dossierId)
            ->andReturn($dossier);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->with($dossier);

        $this->handler->__invoke($event);
    }

    public function testUpdateDossierDoesNothingForNonWooDecision(): void
    {
        $event = new DossierNumberChangedEvent(Uuid::v6(), 'old-nr', 'new-nr', DossierStatus::PUBLISHED);

        $this->dossierRepository->expects('findOneByDossierId')
            ->with($event->dossierId)
            ->andReturn(new Covenant());

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->handler->__invoke($event);
    }
}
