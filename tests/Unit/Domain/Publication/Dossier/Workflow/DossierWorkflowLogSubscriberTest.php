<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflow;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowLogSubscriber;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Webmozart\Assert\InvalidArgumentException;

use function count;
use function sprintf;

final class DossierWorkflowLogSubscriberTest extends UnitTestCase
{
    private LoggerInterface&MockInterface $logger;
    private HistoryService&MockInterface $historyService;
    private WooDecision&MockInterface $dossier;
    private DossierWorkflowLogSubscriber $workflowLog;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->historyService = Mockery::mock(HistoryService::class);
        $this->dossier = Mockery::mock(WooDecision::class);

        $this->workflowLog = new DossierWorkflowLogSubscriber(
            $this->logger,
            $this->historyService,
        );
    }

    public function testGetSubscribedEventsListensToTheEnteredEventOfAllDossierWorkflows(): void
    {
        $subscribedEvents = DossierWorkflowLogSubscriber::getSubscribedEvents();

        self::assertCount(count(DossierWorkflow::cases()), $subscribedEvents);

        foreach (DossierWorkflow::cases() as $case) {
            $eventName = sprintf('workflow.%s.entered', $case->value);

            self::assertArrayHasKey($eventName, $subscribedEvents);
            self::assertSame(['log'], $subscribedEvents[$eventName]);
        }
    }

    public function testLogAddsAHistoryEntryAndLogsTheStateChange(): void
    {
        $dossierId = Uuid::v6();
        $this->dossier->expects('getId')->twice()->andReturn($dossierId);

        $this->historyService->expects('addDossierEntry')->with(
            $dossierId,
            'dossier_state_' . DossierStatus::PUBLISHED->value,
            [
                'old' => '%' . DossierStatus::CONCEPT->value . '%',
                'new' => '%' . DossierStatus::PUBLISHED->value . '%',
            ],
        );

        $this->logger->expects('info')->with('Dossier state changed', [
            'dossier' => $dossierId,
            'oldState' => DossierStatus::CONCEPT->value,
            'newState' => DossierStatus::PUBLISHED->value,
        ]);

        $this->workflowLog->log(
            $this->createEvent($this->dossier, DossierStatus::CONCEPT->value, DossierStatus::PUBLISHED->value),
        );
    }

    public function testLogSkipsTheTechnicalTransitionFromNewToConcept(): void
    {
        $this->historyService->shouldNotReceive('addDossierEntry');
        $this->logger->shouldNotReceive('info');

        $this->workflowLog->log(
            $this->createEvent($this->dossier, DossierStatus::NEW->value, DossierStatus::CONCEPT->value),
        );
    }

    public function testLogSkipsNonMovingTransitions(): void
    {
        $this->historyService->shouldNotReceive('addDossierEntry');
        $this->logger->shouldNotReceive('info');

        $this->workflowLog->log(
            $this->createEvent($this->dossier, DossierStatus::PUBLISHED->value, DossierStatus::PUBLISHED->value),
        );
    }

    public function testLogThrowsExceptionWhenTheSubjectIsNotADossier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->workflowLog->log(
            $this->createEvent(new stdClass(), DossierStatus::CONCEPT->value, DossierStatus::PUBLISHED->value),
        );
    }

    public function testLogThrowsExceptionWhenThereIsNoTransition(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->workflowLog->log(new EnteredEvent($this->dossier, new Marking()));
    }

    public function testLogThrowsExceptionWhenTheTransitionHasNoFromState(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->workflowLog->log(new EnteredEvent(
            $this->dossier,
            new Marking(),
            new Transition('publish', [], DossierStatus::PUBLISHED->value),
        ));
    }

    public function testLogThrowsExceptionWhenTheTransitionHasNoToState(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->workflowLog->log(new EnteredEvent(
            $this->dossier,
            new Marking(),
            new Transition('publish', DossierStatus::CONCEPT->value, []),
        ));
    }

    private function createEvent(object $subject, string $from, string $to): EnteredEvent
    {
        return new EnteredEvent(
            $subject,
            new Marking([$to => 1]),
            new Transition('transition_name', $from, $to),
        );
    }
}
