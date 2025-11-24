<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionException;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionInterface;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminActionService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class DossierAdminActionServiceTest extends UnitTestCase
{
    private DossierAdminActionInterface&MockInterface $actionA;
    private DossierAdminActionInterface&MockInterface $actionB;
    private DossierAdminActionService $actionService;

    protected function setUp(): void
    {
        $this->actionA = \Mockery::mock(DossierAdminActionInterface::class);
        $this->actionB = \Mockery::mock(DossierAdminActionInterface::class);

        $this->actionService = new DossierAdminActionService([
            $this->actionA,
            $this->actionB,
        ]);
    }

    public function testGetAvailableActions(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->actionA->expects('supports')->with($dossier)->andReturnTrue();
        $this->actionA->expects('getAdminAction')->andReturn(DossierAdminAction::INGEST);
        $this->actionB->expects('supports')->with($dossier)->andReturnFalse();

        self::assertEquals(
            [DossierAdminAction::INGEST],
            $this->actionService->getAvailableAdminActions($dossier)
        );
    }

    public function testExecuteUsesFirstMatchingImplementation(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);

        $this->actionA->expects('getAdminAction')->andReturn(DossierAdminAction::INGEST);
        $this->actionA->expects('supports')->with($dossier)->andReturnFalse();

        $this->actionB->expects('getAdminAction')->andReturn(DossierAdminAction::INGEST);
        $this->actionB->expects('supports')->with($dossier)->andReturnTrue();
        $this->actionB->expects('execute')->with($dossier);

        $this->actionService->execute($dossier, DossierAdminAction::INGEST);
    }

    public function testExecuteThrowsExceptionForNoMatchingImplementation(): void
    {
        $dossier = \Mockery::mock(AbstractDossier::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->actionA->expects('getAdminAction')->andReturn(DossierAdminAction::GENERATE_ARCHIVES);

        $this->actionB->expects('getAdminAction')->andReturn(DossierAdminAction::INGEST);
        $this->actionB->expects('supports')->with($dossier)->andReturnFalse();

        $this->expectException(DossierAdminActionException::class);

        $this->actionService->execute($dossier, DossierAdminAction::INGEST);
    }

    public function testNeedsConfirmation(): void
    {
        $this->actionA->shouldReceive('getAdminAction')->andReturn(DossierAdminAction::GENERATE_ARCHIVES);
        $this->actionA->expects('needsConfirmation')->andReturnTrue();

        $this->actionB->shouldReceive('getAdminAction')->andReturn(DossierAdminAction::INGEST);
        $this->actionB->expects('needsConfirmation')->andReturnFalse();

        self::assertTrue(
            $this->actionService->needsConfirmation(DossierAdminAction::GENERATE_ARCHIVES)
        );

        self::assertFalse(
            $this->actionService->needsConfirmation(DossierAdminAction::INGEST)
        );
    }
}
