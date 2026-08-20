<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Handler;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierDetailsCommand;
use Shared\Domain\Publication\Dossier\Handler\UpdateDossierDetailsHandler;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;

class UpdateDossierDetailsHandlerTest extends UnitTestCase
{
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateDossierDetailsHandler $handler;
    private MockInterface&DossierService $dossierService;

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new UpdateDossierDetailsHandler(
            $this->dossierWorkflowManager,
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $covenant = Mockery::mock(Covenant::class);

        $this->dossierWorkflowManager->expects('applyTransition')->with($covenant, DossierStatusTransition::UPDATE_DETAILS);

        $this->dossierService->expects('validateCompletion')->with($covenant);

        $this->handler->__invoke(
            new UpdateDossierDetailsCommand($covenant),
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $covenant = Mockery::mock(Covenant::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($covenant, DossierStatusTransition::UPDATE_DETAILS)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateDossierDetailsCommand($covenant),
        );
    }
}
