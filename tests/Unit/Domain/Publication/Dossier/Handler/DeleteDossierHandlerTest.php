<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Handler\DeleteDossierHandler;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

class DeleteDossierHandlerTest extends MockeryTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private LoggerInterface&MockInterface $logger;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private EntityManagerInterface&MockInterface $entityManager;
    private DeleteDossierHandler $handler;
    private AbstractDossier&MockInterface $dossier;
    private UuidV6 $dossierUuid;
    private DossierDeleteStrategyInterface&MockInterface $strategyA;
    private DossierDeleteStrategyInterface&MockInterface $strategyB;

    public function setUp(): void
    {
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);

        $this->strategyA = \Mockery::mock(DossierDeleteStrategyInterface::class);
        $this->strategyB = \Mockery::mock(DossierDeleteStrategyInterface::class);

        $this->dossierUuid = Uuid::v6();

        $this->dossier = \Mockery::mock(AbstractDossier::class);
        $this->dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        $this->handler = new DeleteDossierHandler(
            $this->dossierRepository,
            $this->logger,
            $this->dossierWorkflowManager,
            $this->entityManager,
            [$this->strategyA, $this->strategyB],
        );
    }

    public function testLogsWarningWhenDossierIsNotFound(): void
    {
        $command = new DeleteDossierCommand($this->dossierUuid);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturnNull();
        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testDeleteSuccessful(): void
    {
        $command = new DeleteDossierCommand($this->dossierUuid);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturn($this->dossier);

        $this->entityManager->expects('beginTransaction');

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::DELETE);
        $this->strategyA->expects('delete')->with($this->dossier);
        $this->strategyB->expects('delete')->with($this->dossier);

        $this->dossierRepository->expects('remove')->with($this->dossier);

        $this->entityManager->expects('commit');

        $this->handler->__invoke($command);
    }

    public function testDeleteRollsBackChangesOnException(): void
    {
        $command = new DeleteDossierCommand($this->dossierUuid);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturn($this->dossier);

        $this->entityManager->expects('beginTransaction');

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::DELETE);
        $this->strategyA->expects('delete')->with($this->dossier)->andThrow($exception = new \RuntimeException('oops'));

        $this->entityManager->expects('rollback');

        $this->expectExceptionObject($exception);
        $this->handler->__invoke($command);
    }
}
