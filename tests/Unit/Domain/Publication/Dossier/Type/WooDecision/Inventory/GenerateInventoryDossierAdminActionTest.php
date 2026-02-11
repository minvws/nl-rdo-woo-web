<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inventory\GenerateInventoryDossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class GenerateInventoryDossierAdminActionTest extends UnitTestCase
{
    private ProductionReportDispatcher&MockInterface $dispatcher;
    private GenerateInventoryDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->dispatcher = Mockery::mock(ProductionReportDispatcher::class);

        $this->action = new GenerateInventoryDossierAdminAction(
            $this->dispatcher,
        );

        parent::setUp();
    }

    public function testGetAdminAction(): void
    {
        self::assertEquals(
            DossierAdminAction::GENERATE_INVENTORY,
            $this->action->getAdminAction(),
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->action->supports(Mockery::mock(WooDecision::class)));
        self::assertFalse($this->action->supports(Mockery::mock(Covenant::class)));
    }

    public function testNeedsConfirmation(): void
    {
        self::assertFalse($this->action->needsConfirmation());
    }

    public function testExecute(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $this->dispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierId);

        $this->action->execute($dossier);
    }
}
