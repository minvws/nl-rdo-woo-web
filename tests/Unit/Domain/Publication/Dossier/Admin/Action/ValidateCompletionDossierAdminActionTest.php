<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use App\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use App\Domain\Publication\Dossier\Admin\Action\ValidateCompletionDossierAdminAction;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

final class ValidateCompletionDossierAdminActionTest extends MockeryTestCase
{
    private DossierService&MockInterface $dossierService;
    private ValidateCompletionDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);

        $this->action = new ValidateCompletionDossierAdminAction(
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testGetAdminAction(): void
    {
        self::assertEquals(
            DossierAdminAction::VALIDATE_COMPLETION,
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

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->action->execute($dossier);
    }
}
