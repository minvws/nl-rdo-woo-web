<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Handler;

use Doctrine\ORM\EntityManagerInterface;
use MinVWS\AuditLogger\AuditLogger;
use MinVWS\AuditLogger\Events\Logging\GeneralLogEvent;
use MinVWS\AuditLogger\Loggers\LoggerInterface as AuditLoggerInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\AuditLog\DossierDeleteLogEvent;
use Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Handler\DeleteDossierHandler;
use Shared\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\Security\AuditUserDetails;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

class DeleteDossierHandlerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private LoggerInterface&MockInterface $logger;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private EntityManagerInterface&MockInterface $entityManager;
    private AuditLoggerInterface&MockInterface $internalAuditLogger;
    private DeleteDossierHandler $handler;
    private AbstractDossier&MockInterface $dossier;
    private UuidV6 $dossierUuid;
    private DossierDeleteStrategyInterface&MockInterface $strategyA;
    private DossierDeleteStrategyInterface&MockInterface $strategyB;
    private UuidV6 $dossierId;
    private string $documentPrefix;
    private string $dossierNr;
    private string $dossierTitle;
    private DossierStatus $dossierStatus;
    private AuditLogger $auditLogger;

    protected function setUp(): void
    {
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->logger = \Mockery::mock(LoggerInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->internalAuditLogger = \Mockery::mock(AuditLoggerInterface::class);
        $this->internalAuditLogger->shouldReceive('canHandleEvent')->andReturnTrue();
        $this->auditLogger = new AuditLogger([$this->internalAuditLogger]);

        $this->strategyA = \Mockery::mock(DossierDeleteStrategyInterface::class);
        $this->strategyB = \Mockery::mock(DossierDeleteStrategyInterface::class);

        $this->dossierUuid = Uuid::v6();
        $this->documentPrefix = 'foo-bar';
        $this->dossierNr = 'foo123';
        $this->dossierTitle = 'Foo Bar';
        $this->dossierStatus = DossierStatus::PUBLISHED;

        $this->dossierId = Uuid::v6();
        $this->dossier = \Mockery::mock(AbstractDossier::class);
        $this->dossier->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);
        $this->dossier->shouldReceive('getId')->andReturn($this->dossierId);
        $this->dossier->shouldReceive('getDocumentPrefix')->andReturn($this->documentPrefix);
        $this->dossier->shouldReceive('getDossierNr')->andReturn($this->dossierNr);
        $this->dossier->shouldReceive('getTitle')->andReturn($this->dossierTitle);
        $this->dossier->shouldReceive('getStatus')->andReturn($this->dossierStatus);

        $this->handler = new DeleteDossierHandler(
            $this->dossierRepository,
            $this->logger,
            $this->dossierWorkflowManager,
            $this->entityManager,
            [$this->strategyA, $this->strategyB],
            $this->auditLogger,
        );
    }

    public function testLogsWarningWhenDossierIsNotFound(): void
    {
        $userDetails = \Mockery::mock(AuditUserDetails::class);
        $command = new DeleteDossierCommand($this->dossierUuid, $userDetails);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturnNull();
        $this->logger->expects('warning');

        $this->handler->__invoke($command);
    }

    public function testDeleteSuccessful(): void
    {
        $userDetails = \Mockery::mock(AuditUserDetails::class);
        $command = new DeleteDossierCommand($this->dossierUuid, $userDetails);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturn($this->dossier);

        $this->entityManager->expects('beginTransaction');

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::DELETE);
        $this->strategyA->expects('delete')->with($this->dossier);
        $this->strategyB->expects('delete')->with($this->dossier);

        $this->dossierRepository->expects('remove')->with($this->dossier);

        $this->entityManager->expects('commit');

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (DossierDeleteLogEvent $event) use ($userDetails): bool {
                $this->assertEquals($userDetails, $event->getActor());
                $this->assertEquals(GeneralLogEvent::AC_DELETE, $event->actionCode);
                $this->assertEquals('woo', $event->source);
                $this->assertEquals(
                    [
                        'id' => $this->dossierId->toRfc4122(),
                        'prefix' => $this->documentPrefix,
                        'dossier_nr' => $this->dossierNr,
                        'title' => $this->dossierTitle,
                        'status' => $this->dossierStatus->value,
                    ],
                    $event->data,
                );
                $this->assertFalse($event->failed);

                return true;
            }
        ));

        $this->handler->__invoke($command);
    }

    public function testDeleteWithOverrideSuccessful(): void
    {
        $userDetails = \Mockery::mock(AuditUserDetails::class);
        $command = new DeleteDossierCommand($this->dossierUuid, $userDetails, overrideWorkflow: true);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturn($this->dossier);

        $this->entityManager->expects('beginTransaction');

        $this->dossierWorkflowManager->shouldNotReceive('applyTransition');
        $this->strategyA->expects('deleteWithOverride')->with($this->dossier);
        $this->strategyB->expects('deleteWithOverride')->with($this->dossier);

        $this->dossierRepository->expects('remove')->with($this->dossier);

        $this->entityManager->expects('commit');

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (DossierDeleteLogEvent $event) use ($userDetails): bool {
                $this->assertEquals($userDetails, $event->getActor());
                $this->assertEquals(GeneralLogEvent::AC_DELETE, $event->actionCode);
                $this->assertEquals('woo', $event->source);
                $this->assertEquals(
                    [
                        'id' => $this->dossierId->toRfc4122(),
                        'prefix' => $this->documentPrefix,
                        'dossier_nr' => $this->dossierNr,
                        'title' => $this->dossierTitle,
                        'status' => $this->dossierStatus->value,
                    ],
                    $event->data,
                );
                $this->assertFalse($event->failed);

                return true;
            }
        ));

        $this->handler->__invoke($command);
    }

    public function testDeleteRollsBackChangesOnException(): void
    {
        $userDetails = \Mockery::mock(AuditUserDetails::class);
        $command = new DeleteDossierCommand($this->dossierUuid, $userDetails);

        $this->dossierRepository->expects('find')->with($this->dossierUuid)->andReturn($this->dossier);

        $this->entityManager->expects('beginTransaction');

        $this->dossierWorkflowManager->expects('applyTransition')->with($this->dossier, DossierStatusTransition::DELETE);
        $this->strategyA->expects('delete')->with($this->dossier)->andThrow($exception = new \RuntimeException('oops'));

        $this->entityManager->expects('rollback');

        $this->logger->expects('error');

        $this->internalAuditLogger->expects('log')->with(\Mockery::on(
            function (DossierDeleteLogEvent $event) use ($userDetails): bool {
                $this->assertEquals($userDetails, $event->getActor());
                $this->assertEquals(GeneralLogEvent::AC_DELETE, $event->actionCode);
                $this->assertEquals('woo', $event->source);
                $this->assertEquals(
                    [
                        'id' => $this->dossierId->toRfc4122(),
                        'prefix' => $this->documentPrefix,
                        'dossier_nr' => $this->dossierNr,
                        'title' => $this->dossierTitle,
                        'status' => $this->dossierStatus->value,
                    ],
                    $event->data,
                );
                $this->assertTrue($event->failed);

                return true;
            }
        ));

        $this->expectExceptionObject($exception);
        $this->handler->__invoke($command);
    }
}
