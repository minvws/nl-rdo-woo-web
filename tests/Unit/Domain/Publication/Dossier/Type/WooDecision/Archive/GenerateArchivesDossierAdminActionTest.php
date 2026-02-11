<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Archive;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\BatchDownload\BatchDownloadScope;
use Shared\Domain\Publication\BatchDownload\BatchDownloadService;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Archive\GenerateArchivesDossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;

final class GenerateArchivesDossierAdminActionTest extends UnitTestCase
{
    private BatchDownloadService&MockInterface $batchDownloadService;
    private GenerateArchivesDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->batchDownloadService = Mockery::mock(BatchDownloadService::class);

        $this->action = new GenerateArchivesDossierAdminAction(
            $this->batchDownloadService,
        );

        parent::setUp();
    }

    public function testGetAdminAction(): void
    {
        self::assertEquals(
            DossierAdminAction::GENERATE_ARCHIVES,
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

        $this->batchDownloadService->expects('refresh')->with(Mockery::on(
            static fn (BatchDownloadScope $scope): bool => $scope->wooDecision === $dossier
        ));

        $this->action->execute($dossier);
    }
}
