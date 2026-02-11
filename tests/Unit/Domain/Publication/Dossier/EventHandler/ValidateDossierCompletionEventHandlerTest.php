<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\EventHandler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\EventHandler\ValidateDossierCompletionEventHandler;
use Shared\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class ValidateDossierCompletionEventHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $repository;
    private DossierService&MockInterface $dossierService;
    private ValidateDossierCompletionEventHandler $handler;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(DossierRepository::class);
        $this->dossierService = Mockery::mock(DossierService::class);

        $this->handler = new ValidateDossierCompletionEventHandler(
            $this->repository,
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testHandleDocumentFileSetProcessedEvent(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossierId = Uuid::v6();

        $event = new DocumentFileSetProcessedEvent($dossierId);

        $this->repository->expects('findOneByDossierId')->with($dossierId)->andReturn($dossier);

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->handleDocumentFileSetProcessedEvent($event);
    }
}
