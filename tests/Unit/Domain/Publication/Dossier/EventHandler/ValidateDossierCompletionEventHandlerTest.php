<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\EventHandler;

use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\EventHandler\ValidateDossierCompletionEventHandler;
use App\Domain\Publication\Dossier\Type\WooDecision\DocumentFile\Event\DocumentFileSetProcessedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class ValidateDossierCompletionEventHandlerTest extends MockeryTestCase
{
    private DossierRepository&MockInterface $repository;
    private DossierService&MockInterface $dossierService;
    private ValidateDossierCompletionEventHandler $handler;

    public function setUp(): void
    {
        $this->repository = \Mockery::mock(DossierRepository::class);
        $this->dossierService = \Mockery::mock(DossierService::class);

        $this->handler = new ValidateDossierCompletionEventHandler(
            $this->repository,
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testHandleDocumentFileSetProcessedEvent(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossierId = Uuid::v6();

        $event = new DocumentFileSetProcessedEvent($dossierId);

        $this->repository->expects('findOneByDossierId')->with($dossierId)->andReturn($dossier);

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->handler->handleDocumentFileSetProcessedEvent($event);
    }
}
