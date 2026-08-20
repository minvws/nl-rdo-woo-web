<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Workflow;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\Exception\TransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

class DossierWorkflowManagerTest extends UnitTestCase
{
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private WooDecision&MockInterface $dossier;
    private WorkflowInterface&MockInterface $workflow;
    private DossierWorkflowManager $manager;
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->dossierTypeManager = Mockery::mock(DossierTypeManager::class);

        $this->dossier = Mockery::mock(WooDecision::class);

        $this->workflow = Mockery::mock(WorkflowInterface::class);

        $this->manager = new DossierWorkflowManager(
            $this->logger,
            $this->dossierTypeManager,
        );
    }

    public function testIsTransitionAllowedReturnsFalseWhenTheWorkflowDeniesATransition(): void
    {
        $this->dossierTypeManager->expects('getStatusWorkflow')->andReturn($this->workflow);

        $this->workflow->expects('can')->with($this->dossier, DossierStatusTransition::PUBLISH->value)->andReturnFalse();

        self::assertFalse(
            $this->manager->isTransitionAllowed($this->dossier, DossierStatusTransition::PUBLISH),
        );
    }

    public function testApplyTransitionThrowsExceptionForInvalidTransition(): void
    {
        $this->dossier->expects('getId')->times(3)->andReturn(Uuid::v6());
        $this->dossier->expects('getStatus')->andReturn(DossierStatus::NEW);

        $this->dossierTypeManager->expects('getStatusWorkflow')->andReturn($this->workflow);

        $this->workflow->expects('apply')
            ->with($this->dossier, DossierStatusTransition::PUBLISH->value)
            ->andThrow(new TransitionException($this->dossier, DossierStatusTransition::PUBLISH->value, $this->workflow, 'foo'));

        $this->logger->expects('error');

        $this->expectExceptionObject(
            DossierWorkflowException::forTransitionFailed(
                $this->dossier,
                DossierStatusTransition::PUBLISH,
                Mockery::mock(TransitionException::class),
            ),
        );
        $this->manager->applyTransition($this->dossier, DossierStatusTransition::PUBLISH);
    }
}
