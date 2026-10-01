<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\LinkedWooDecisionUpdater;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class LinkedWooDecisionUpdaterTest extends UnitTestCase
{
    private DossierWorkflowManager&MockInterface $dossierWorkflowManager;
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private LinkedWooDecisionUpdater $updater;

    protected function setUp(): void
    {
        $this->dossierWorkflowManager = Mockery::mock(DossierWorkflowManager::class);
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);

        $this->updater = new LinkedWooDecisionUpdater(
            $this->dossierWorkflowManager,
            $this->wooDecisionRepository,
        );

        parent::setUp();
    }

    public function testUpdateLinkedToAppliesTheTransitionOnceForASingleDecisionDocument(): void
    {
        $wooDecision = $this->createWooDecision();
        $documentId = Uuid::v6();

        $this->expectLinkedWooDecisions([$documentId], $wooDecision);
        $this->expectTransitionAllowed($wooDecision);
        $this->expectTransitionApplied($wooDecision);

        $this->updater->updateLinkedTo($wooDecision, [$documentId]);
    }

    public function testUpdateLinkedToAppliesTheTransitionToEveryLinkedDecision(): void
    {
        $decisionA = $this->createWooDecision();
        $decisionB = $this->createWooDecision();
        $documentId = Uuid::v6();

        $this->expectLinkedWooDecisions([$documentId], $decisionA, $decisionB);
        $this->expectTransitionAllowed($decisionB, $decisionA);
        $this->expectTransitionApplied($decisionB, $decisionA);

        $this->updater->updateLinkedTo($decisionB, [$documentId]);
    }

    public function testUpdateLinkedToAppliesTheTransitionWithoutAnyDocuments(): void
    {
        $wooDecision = $this->createWooDecision();

        $this->expectLinkedWooDecisions([]);
        $this->expectTransitionAllowed($wooDecision);
        $this->expectTransitionApplied($wooDecision);

        $this->updater->updateLinkedTo($wooDecision, []);
    }

    public function testUpdateLinkedToAppliesNothingWhenOneDecisionRejectsTheTransition(): void
    {
        $decisionA = $this->createWooDecision();
        $decisionB = $this->createWooDecision();
        $documentId = Uuid::v6();

        $this->expectLinkedWooDecisions([$documentId], $decisionA, $decisionB);

        $this->dossierWorkflowManager
            ->allows('isTransitionAllowed')
            ->with($decisionB, DossierStatusTransition::UPDATE_DOCUMENTS)
            ->andReturnTrue();
        $this->dossierWorkflowManager
            ->allows('isTransitionAllowed')
            ->with($decisionA, DossierStatusTransition::UPDATE_DOCUMENTS)
            ->andReturnFalse();
        $this->dossierWorkflowManager->expects('applyTransition')->never();

        $this->expectException(DossierWorkflowException::class);

        $this->updater->updateLinkedTo($decisionB, [$documentId]);
    }

    public function testUpdateOtherLinkedToSkipsTheGivenDecision(): void
    {
        $decisionA = $this->createWooDecision();
        $decisionB = $this->createWooDecision();
        $documentId = Uuid::v6();

        $this->expectLinkedWooDecisions([$documentId], $decisionA, $decisionB);
        $this->expectTransitionAllowed($decisionA);
        $this->expectTransitionApplied($decisionA);

        $this->updater->updateOtherLinkedTo($decisionB, [$documentId]);
    }

    public function testUpdateOtherLinkedToAppliesNothingForASingleDecisionDocument(): void
    {
        $wooDecision = $this->createWooDecision();
        $documentId = Uuid::v6();

        $this->expectLinkedWooDecisions([$documentId], $wooDecision);

        $this->dossierWorkflowManager->expects('isTransitionAllowed')->never();
        $this->dossierWorkflowManager->expects('applyTransition')->never();

        $this->updater->updateOtherLinkedTo($wooDecision, [$documentId]);
    }

    private function createWooDecision(): WooDecision&MockInterface
    {
        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->allows('getId')->andReturn(Uuid::v6());

        return $wooDecision;
    }

    /**
     * @param list<Uuid> $documentIds
     */
    private function expectLinkedWooDecisions(array $documentIds, WooDecision ...$wooDecisions): void
    {
        $this->wooDecisionRepository
            ->expects('findAllLinkedToDocuments')
            ->with($documentIds)
            ->andReturn($wooDecisions);
    }

    private function expectTransitionAllowed(WooDecision ...$wooDecisions): void
    {
        foreach ($wooDecisions as $wooDecision) {
            $this->dossierWorkflowManager
                ->expects('isTransitionAllowed')
                ->with($wooDecision, DossierStatusTransition::UPDATE_DOCUMENTS)
                ->andReturnTrue();
        }
    }

    private function expectTransitionApplied(WooDecision ...$wooDecisions): void
    {
        foreach ($wooDecisions as $wooDecision) {
            $this->dossierWorkflowManager
                ->expects('applyTransition')
                ->with($wooDecision, DossierStatusTransition::UPDATE_DOCUMENTS);
        }
    }
}
