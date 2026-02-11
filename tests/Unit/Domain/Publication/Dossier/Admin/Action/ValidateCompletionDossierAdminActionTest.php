<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Admin\Action;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Admin\Action\DossierAdminAction;
use Shared\Domain\Publication\Dossier\Admin\Action\ValidateCompletionDossierAdminAction;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;

final class ValidateCompletionDossierAdminActionTest extends UnitTestCase
{
    private DossierService&MockInterface $dossierService;
    private ValidateCompletionDossierAdminAction $action;

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);

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
        self::assertTrue($this->action->supports(Mockery::mock(WooDecision::class)));
        self::assertTrue($this->action->supports(Mockery::mock(Covenant::class)));
    }

    public function testNeedsConfirmation(): void
    {
        self::assertFalse($this->action->needsConfirmation());
    }

    public function testExecute(): void
    {
        $dossier = Mockery::mock(WooDecision::class);

        $this->dossierService->expects('validateCompletion')->with($dossier);

        $this->action->execute($dossier);
    }
}
