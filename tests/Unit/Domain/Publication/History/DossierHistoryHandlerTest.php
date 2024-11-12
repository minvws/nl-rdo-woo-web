<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\History;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\History\DossierHistoryHandler;
use App\Service\HistoryService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class DossierHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private DossierRepository&MockInterface $repository;
    private DossierHistoryHandler $handler;

    public function setUp(): void
    {
        $this->historyService = \Mockery::mock(HistoryService::class);
        $this->repository = \Mockery::mock(DossierRepository::class);

        $this->handler = new DossierHistoryHandler(
            $this->historyService,
            $this->repository,
        );

        parent::setUp();
    }

    public function testHandleCreated(): void
    {
        $dossier = \Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = DossierCreatedEvent::forDossier($dossier);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier,
                'dossier_created',
            )
            ->once();

        $this->handler->handleCreated($event);
    }
}
