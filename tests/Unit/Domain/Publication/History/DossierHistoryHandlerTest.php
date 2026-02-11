<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Event\DossierCreatedEvent;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\History\DossierHistoryHandler;
use Shared\Service\HistoryService;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class DossierHistoryHandlerTest extends UnitTestCase
{
    private HistoryService&MockInterface $historyService;
    private DossierRepository&MockInterface $repository;
    private DossierHistoryHandler $handler;
    private ApplicationMode $applicationMode;

    protected function setUp(): void
    {
        $this->historyService = Mockery::mock(HistoryService::class);
        $this->repository = Mockery::mock(DossierRepository::class);
        $this->applicationMode = ApplicationMode::ALL;

        $this->handler = new DossierHistoryHandler(
            $this->historyService,
            $this->repository,
            $this->applicationMode,
        );

        parent::setUp();
    }

    public function testHandleCreated(): void
    {
        $dossierStatus = DossierStatus::CONCEPT;

        $dossier = Mockery::mock(AnnualReport::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());
        $dossier->shouldReceive('getStatus')->andReturn($dossierStatus);

        $this->repository->shouldReceive('findOneByDossierId')->with($dossier->getId())->andReturn($dossier);

        $event = DossierCreatedEvent::forDossier($dossier);

        $this->historyService
            ->expects('addDossierEntry')
            ->with(
                $dossier->getId(),
                'dossier_created',
                [
                    'applicationMode' => $this->applicationMode->value,
                    'status' => $dossierStatus->value,
                ],
            )
            ->once();

        $this->handler->handleCreated($event);
    }
}
