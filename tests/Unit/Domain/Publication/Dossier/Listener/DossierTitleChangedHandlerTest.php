<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Listener;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Event\DossierTitleChangedEvent;
use Shared\Domain\Publication\Dossier\Listener\DossierTitleChangedHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;

class DossierTitleChangedHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private DossierDispatcher&MockInterface $dossierDispatcher;

    private DossierTitleChangedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossierRepository = Mockery::mock(DossierRepository::class);
        $this->dossierDispatcher = Mockery::mock(DossierDispatcher::class);

        $this->handler = new DossierTitleChangedHandler(
            $this->dossierRepository,
            $this->dossierDispatcher,
        );
    }

    public function testUpdateDossier(): void
    {
        $dossier = new WooDecision();
        $oldTitle = DossierTitle::create('Old Title');
        $newTitle = DossierTitle::create('New Title');
        $event = new DossierTitleChangedEvent($dossier->getId(), $oldTitle, $newTitle);

        $this->dossierRepository->expects('findOneByDossierId')
            ->with($event->dossierId)
            ->andReturn($dossier);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->with($dossier);

        $this->handler->__invoke($event);
    }
}
