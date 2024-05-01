<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\Handler;

use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantContentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantUpdatedEvent;
use App\Domain\Publication\Dossier\Type\Covenant\Handler\UpdateCovenantContentHandler;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\DossierService;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateCovenantContentHandlerTest extends MockeryTestCase
{
    private MessageBusInterface&MockInterface $messageBus;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private UpdateCovenantContentHandler $handler;
    private MockInterface&DossierService $dossierService;

    public function setUp(): void
    {
        $this->dossierService = \Mockery::mock(DossierService::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);
        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);

        $this->handler = new UpdateCovenantContentHandler(
            $this->dossierWorkflowManager,
            $this->dossierService,
            $this->messageBus,
        );

        parent::setUp();
    }

    public function testInvokeSuccessfully(): void
    {
        $covenantUuid = Uuid::v6();
        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getId')->andReturn($covenantUuid);

        $this->dossierWorkflowManager->expects('applyTransition')->with($covenant, DossierStatusTransition::UPDATE_CONTENT);

        $this->dossierService->expects('validateCompletion')->with($covenant);
        $this->dossierService->expects('updateHistory')->with($covenant);

        $this->messageBus->expects('dispatch')->with(\Mockery::on(
            static function (CovenantUpdatedEvent $message) use ($covenantUuid) {
                self::assertEquals($covenantUuid, $message->id);

                return true;
            }
        ))->andReturns(new Envelope(new \stdClass()));

        $this->handler->__invoke(
            new UpdateCovenantContentCommand($covenant)
        );
    }

    public function testInvokeThrowsExceptionWhenTransitionIsNotAllowed(): void
    {
        $covenant = \Mockery::mock(Covenant::class);

        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($covenant, DossierStatusTransition::UPDATE_CONTENT)
            ->andThrow(new DossierWorkflowException());

        $this->expectException(DossierWorkflowException::class);

        $this->handler->__invoke(
            new UpdateCovenantContentCommand($covenant)
        );
    }
}
