<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\IndexDossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class IndexDossierAdminActionTest extends UnitTestCase
{
    private SearchDispatcher&MockInterface $searchDispatcher;
    private IndexDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->searchDispatcher = \Mockery::mock(SearchDispatcher::class);

        $this->action = new IndexDossierAdminAction(
            $this->searchDispatcher,
        );

        parent::setUp();
    }

    public function testGetAdminAction(): void
    {
        self::assertEquals(
            DossierAdminAction::INDEX,
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
        self::assertFalse($this->action->needsConfirmation());
    }

    public function testExecute(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $this->searchDispatcher->expects('dispatchIndexDossierCommand')->with($dossierId);

        $this->action->execute($dossier);
    }
}
