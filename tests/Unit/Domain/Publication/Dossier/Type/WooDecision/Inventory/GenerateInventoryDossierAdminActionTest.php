<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Inventory;

use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Inventory\GenerateInventoryDossierAdminAction;
use App\Domain\Publication\Dossier\Type\WooDecision\ProductionReport\ProductionReportDispatcher;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class GenerateInventoryDossierAdminActionTest extends MockeryTestCase
{
    private ProductionReportDispatcher&MockInterface $dispatcher;
    private GenerateInventoryDossierAdminAction $action;

    public function setUp(): void
    {
        $this->dispatcher = \Mockery::mock(ProductionReportDispatcher::class);

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
        self::assertTrue($this->action->supports(\Mockery::mock(WooDecision::class)));
        self::assertFalse($this->action->supports(\Mockery::mock(Covenant::class)));
    }

    public function testExecute(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $this->dispatcher->expects('dispatchGenerateInventoryCommand')->with($dossierId);

        $this->action->execute($dossier);
    }
}
