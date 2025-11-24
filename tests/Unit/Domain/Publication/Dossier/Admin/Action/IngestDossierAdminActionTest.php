<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use Mockery\MockInterface;
use Shared\Domain\Ingest\IngestDispatcher;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\IngestDossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class IngestDossierAdminActionTest extends UnitTestCase
{
    private IngestDispatcher&MockInterface $ingestDispatcher;
    private IngestDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->ingestDispatcher = \Mockery::mock(IngestDispatcher::class);

        $this->action = new IngestDossierAdminAction(
            $this->ingestDispatcher,
        );

        parent::setUp();
    }

    public function testGetAdminAction(): void
    {
        self::assertEquals(
            DossierAdminAction::INGEST,
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

        $this->ingestDispatcher->expects('dispatchIngestDossierCommand')->with($dossier);

        $this->action->execute($dossier);
    }
}
