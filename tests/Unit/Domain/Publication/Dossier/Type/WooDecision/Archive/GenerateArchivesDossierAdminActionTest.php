<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Archive;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Archive\GenerateArchivesDossierAdminAction;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class GenerateArchivesDossierAdminActionTest extends MockeryTestCase
{
    private BatchDownloadService&MockInterface $batchDownloadService;
    private GenerateArchivesDossierAdminAction $action;

    public function setUp(): void
    {
        $this->batchDownloadService = \Mockery::mock(BatchDownloadService::class);

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
        self::assertTrue($this->action->supports(\Mockery::mock(WooDecision::class)));
        self::assertFalse($this->action->supports(\Mockery::mock(Covenant::class)));
    }

    public function testExecute(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);

        $this->batchDownloadService->expects('refresh')->with(\Mockery::on(
            static function (BatchDownloadScope $scope) use ($dossier): bool {
                return $scope->wooDecision === $dossier;
            }
        ));

        $this->action->execute($dossier);
    }
}
