<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\Admin\Action\DeleteDossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\DossierDispatcher;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

final class DeleteDossierAdminActionTest extends MockeryTestCase
{
    private DossierDispatcher&MockInterface $dossierDispatcher;
    private DeleteDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->dossierDispatcher = \Mockery::mock(DossierDispatcher::class);

        $this->action = new DeleteDossierAdminAction(
            $this->dossierDispatcher,
        );

        parent::setUp();
    }

    public function testGetAdminAction(): void
    {
        self::assertEquals(
            DossierAdminAction::DELETE,
            $this->action->getAdminAction(),
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->action->supports(\Mockery::mock(WooDecision::class)));
        self::assertTrue($this->action->supports(\Mockery::mock(Covenant::class)));
    }

    public function testNeedsConfirmation(): void
    {
        self::assertTrue($this->action->needsConfirmation());
    }

    public function testExecute(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $this->dossierDispatcher->expects('dispatchDeleteDossierCommand')->with($dossierId, true);

        $this->action->execute($dossier);
    }
}
