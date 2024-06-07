<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Publication\Dossier\DossierPublisher;
use App\Domain\Publication\Dossier\Event\DossierPublishedEvent;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DossierPublisherTest extends MockeryTestCase
{
    private WooDecision&MockInterface $dossier;
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private MessageBusInterface&MockInterface $messageBus;
    private DossierPublisher $publisher;

    public function setUp(): void
    {
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->dossierWorkflowManager = \Mockery::mock(DossierWorkflowManager::class);
        $this->messageBus = \Mockery::mock(MessageBusInterface::class);

        $this->publisher = new DossierPublisher(
            $this->dossierWorkflowManager,
            $this->messageBus,
        );
    }

    public function testCanPublish(): void
    {
        $this->dossierWorkflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::PUBLISH)
            ->andReturnTrue();

        self::assertTrue($this->publisher->canPublish($this->dossier));
    }

    public function testPublish(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::PUBLISH);

        $this->messageBus->expects('dispatch')
            ->with(\Mockery::type(DossierPublishedEvent::class))
            ->andReturns(new Envelope(new \stdClass()));

        $this->publisher->publish($this->dossier);
    }

    public function testCanPublishAsPreview(): void
    {
        $this->dossierWorkflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::PUBLISH_AS_PREVIEW)
            ->andReturnTrue();

        self::assertTrue($this->publisher->canPublishAsPreview($this->dossier));
    }

    public function testPublishAsPreview(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::PUBLISH_AS_PREVIEW);

        $this->publisher->publishAsPreview($this->dossier);
    }

    public function testCanSchedulePublication(): void
    {
        $this->dossierWorkflowManager
            ->expects('isTransitionAllowed')
            ->with($this->dossier, DossierStatusTransition::SCHEDULE)
            ->andReturnTrue();

        self::assertTrue($this->publisher->canSchedulePublication($this->dossier));
    }

    public function testSchedulePublication(): void
    {
        $this->dossierWorkflowManager
            ->expects('applyTransition')
            ->with($this->dossier, DossierStatusTransition::SCHEDULE);

        $this->publisher->schedulePublication($this->dossier);
    }
}
