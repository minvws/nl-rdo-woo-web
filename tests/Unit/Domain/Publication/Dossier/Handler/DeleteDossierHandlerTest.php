<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\DossierDeleteHelper;
use App\Domain\Publication\Dossier\Handler\DeleteDossierHandler;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

class DeleteDossierHandlerTest extends MockeryTestCase
{
    private DossierDeleteHelper&MockInterface $deleteHelper;
    private AbstractDossierRepository&MockInterface $dossierRepository;
    private LoggerInterface&MockInterface $logger;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private DeleteDossierHandler $handler;
    private AbstractDossier&MockInterface $dossier;
    private UuidV6 $dossierUuid;

    public function setUp(): void
    {
        $this->deleteHelper = \Mockery::mock(DossierDeleteHelper::class);
        $this->dossierRepository = \Mockery::mock(AbstractDossierRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->dossierTypeManager = \Mockery::mock(DossierTypeManager::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->dossierUuid = Uuid::v6();

        $this->dossier = \Mockery::mock(AbstractDossier::class);
        $this->dossier->shouldReceive('getId')->andReturn($this->dossierUuid);
        $this->dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        $this->handler = new DeleteDossierHandler(
            $this->deleteHelper,
            $this->dossierRepository,
            $this->logger,
            $this->dossierTypeManager,
            $this->dossierWorkflowManager,
        );
    }

    public function testLogsWarningWhenDossierIsNotFound(): void
    {
        $command = DeleteDossierCommand::forDossier($this->dossier);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturnNull();
        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testDeleteSuccessful(): void
    {
        $command = DeleteDossierCommand::forDossier($this->dossier);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturn($this->dossier);

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::DELETE);
        $this->deleteHelper->expects('deleteFromElasticSearch')->with($this->dossier);

        $typeConfig = \Mockery::mock(DossierTypeConfigInterface::class);
        $typeConfig->expects('getDeleteStrategy->delete')->with($this->dossier);
        $this->dossierTypeManager->expects('getConfig')->with(DossierType::WOO_DECISION)->andReturn($typeConfig);

        $this->deleteHelper->expects('delete')->with($this->dossier);

        $this->handler->__invoke($command);
    }
}
