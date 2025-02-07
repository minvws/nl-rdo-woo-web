<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Admin;

use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Admin\GenerateArchivesDossierAdminAction;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class GenerateArchivesDossierAdminActionTest extends MockeryTestCase
{
    private DossierService&MockInterface $dossierService;
    private GenerateArchivesDossierAdminAction $action;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);

        $this->action = new GenerateArchivesDossierAdminAction(
            $this->dossierService,
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

        $this->dossierService->expects('generateArchives')->with($dossier);

        $this->action->execute($dossier);
    }
}
