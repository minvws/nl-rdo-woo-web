<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Ingest\IngestDispatcher;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\IngestDossierAdminAction;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class IngestDossierAdminActionTest extends MockeryTestCase
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
