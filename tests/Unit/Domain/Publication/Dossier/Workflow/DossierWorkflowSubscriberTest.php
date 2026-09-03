<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Domain\Publication\Dossier\DossierDispatcher;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflow;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowSubscriber;
use Shared\Service\DossierService;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Webmozart\Assert\InvalidArgumentException;

use function count;
use function sprintf;

final class DossierWorkflowSubscriberTest extends UnitTestCase
{
    private DossierService&MockInterface $dossierService;
    private DossierDispatcher&MockInterface $dossierDispatcher;
    private WooDecision&MockInterface $dossier;
    private DossierWorkflowSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->dossierService = Mockery::mock(DossierService::class);
        $this->dossierDispatcher = Mockery::mock(DossierDispatcher::class);
        $this->dossier = Mockery::mock(WooDecision::class);

        $this->subscriber = new DossierWorkflowSubscriber(
            $this->dossierService,
            $this->dossierDispatcher,
        );
    }

    public function testGetSubscribedEventsListensToTheEnteredAndCompletedEventOfAllDossierWorkflows(): void
    {
        $subscribedEvents = DossierWorkflowSubscriber::getSubscribedEvents();

        self::assertCount(count(DossierWorkflow::cases()) * 2, $subscribedEvents);

        foreach (DossierWorkflow::cases() as $case) {
            $enteredEventName = sprintf('workflow.%s.entered', $case->value);
            $completedEventName = sprintf('workflow.%s.completed', $case->value);

            self::assertArrayHasKey($enteredEventName, $subscribedEvents);
            self::assertSame(['handleEntityUpdate'], $subscribedEvents[$enteredEventName]);

            self::assertArrayHasKey($completedEventName, $subscribedEvents);
            self::assertSame(['synchronizeArtifacts'], $subscribedEvents[$completedEventName]);
        }
    }

    public function testHandleEntityUpdateForwardsTheDossierToTheDossierService(): void
    {
        $this->dossierService->expects('validateCompletion')->with($this->dossier);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->with($this->dossier);

        $this->subscriber->handleEntityUpdate(new EnteredEvent(
            $this->dossier,
            new Marking([DossierStatus::PUBLISHED->value => 1]),
            new Transition('publish', DossierStatus::CONCEPT->value, DossierStatus::PUBLISHED->value),
        ));
    }

    public function testHandleEntityUpdateThrowsExceptionWhenTheSubjectIsNotADossier(): void
    {
        $this->dossierService->expects('handleEntityUpdate')->never();

        $this->expectException(InvalidArgumentException::class);

        $this->subscriber->handleEntityUpdate(new EnteredEvent(new stdClass(), new Marking()));
    }

    public function testSynchronizeArtifactsDispatchesTheCommandForAWooDecision(): void
    {
        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->with($this->dossier);

        $this->subscriber->synchronizeArtifacts(
            $this->createEvent($this->dossier, DossierStatus::NEW->value, DossierStatus::CONCEPT->value),
        );
    }

    public function testSynchronizeArtifactsSkipsDossiersOtherThanWooDecisions(): void
    {
        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->subscriber->synchronizeArtifacts(
            $this->createEvent(
                Mockery::mock(Covenant::class),
                DossierStatus::NEW->value,
                DossierStatus::CONCEPT->value,
            ),
        );
    }

    /**
     * @return array<string,array{0:DossierStatus}>
     */
    public static function publiclyAvailableStatusProvider(): array
    {
        return [
            'published' => [DossierStatus::PUBLISHED],
            'preview' => [DossierStatus::PREVIEW],
        ];
    }

    #[DataProvider('publiclyAvailableStatusProvider')]
    public function testSynchronizeArtifactsSkipsPubliclyAvailableDossiers(DossierStatus $status): void
    {
        $this->dossier->expects('getStatus')->andReturn($status);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->subscriber->synchronizeArtifacts(
            $this->createEvent($this->dossier, DossierStatus::CONCEPT->value, $status->value),
        );
    }

    public function testSynchronizeArtifactsSkipsNonMovingTransitions(): void
    {
        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->subscriber->synchronizeArtifacts(
            $this->createEvent($this->dossier, DossierStatus::CONCEPT->value, DossierStatus::CONCEPT->value),
        );
    }

    public function testSynchronizeArtifactsThrowsExceptionWhenTheSubjectIsNotADossier(): void
    {
        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->expectException(InvalidArgumentException::class);

        $this->subscriber->synchronizeArtifacts(
            $this->createEvent(new stdClass(), DossierStatus::NEW->value, DossierStatus::CONCEPT->value),
        );
    }

    public function testSynchronizeArtifactsThrowsExceptionWhenThereIsNoTransition(): void
    {
        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->expectException(InvalidArgumentException::class);

        $this->subscriber->synchronizeArtifacts(new CompletedEvent($this->dossier, new Marking()));
    }

    public function testSynchronizeArtifactsThrowsExceptionWhenTheTransitionHasNoFromState(): void
    {
        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->expectException(InvalidArgumentException::class);

        $this->subscriber->synchronizeArtifacts(new CompletedEvent(
            $this->dossier,
            new Marking(),
            new Transition('publish', [], DossierStatus::PUBLISHED->value),
        ));
    }

    public function testSynchronizeArtifactsThrowsExceptionWhenTheTransitionHasNoToState(): void
    {
        $this->dossier->expects('getStatus')->andReturn(DossierStatus::CONCEPT);

        $this->dossierDispatcher->expects('dispatchSynchronizeArtifactsCommand')->never();

        $this->expectException(InvalidArgumentException::class);

        $this->subscriber->synchronizeArtifacts(new CompletedEvent(
            $this->dossier,
            new Marking(),
            new Transition('publish', DossierStatus::CONCEPT->value, []),
        ));
    }

    private function createEvent(object $subject, string $from, string $to): CompletedEvent
    {
        return new CompletedEvent(
            $subject,
            new Marking([$to => 1]),
            new Transition('transition_name', $from, $to),
        );
    }
}
