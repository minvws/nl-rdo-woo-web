<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document;

use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Symfony\Component\Uid\Uuid;

use function array_filter;
use function array_values;

readonly class LinkedWooDecisionUpdater
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    /**
     * @param list<Uuid> $documentIds
     */
    public function updateLinkedTo(WooDecision $wooDecision, array $documentIds): void
    {
        $this->update([$wooDecision, ...$this->wooDecisionRepository->findAllLinkedToDocuments($documentIds)]);
    }

    /**
     * @param list<Uuid> $documentIds
     */
    public function updateOtherLinkedTo(WooDecision $wooDecision, array $documentIds): void
    {
        $this->update(array_filter(
            $this->wooDecisionRepository->findAllLinkedToDocuments($documentIds),
            static fn (WooDecision $linked): bool => ! $linked->getId()->equals($wooDecision->getId()),
        ));
    }

    /**
     * @param iterable<WooDecision> $wooDecisions
     */
    private function update(iterable $wooDecisions): void
    {
        $wooDecisions = $this->deduplicate($wooDecisions);

        $this->isTransitionAllowedForAllWooDecisions($wooDecisions);

        foreach ($wooDecisions as $wooDecision) {
            $this->dossierWorkflowManager->applyTransition($wooDecision, DossierStatusTransition::UPDATE_DOCUMENTS);
        }
    }

    /**
     * @param iterable<WooDecision> $wooDecisions
     *
     * @return list<WooDecision>
     */
    private function deduplicate(iterable $wooDecisions): array
    {
        $unique = [];
        foreach ($wooDecisions as $wooDecision) {
            $unique[$wooDecision->getId()->toRfc4122()] = $wooDecision;
        }

        return array_values($unique);
    }

    /**
     * @param iterable<WooDecision> $wooDecisions
     *
     * @throws DossierWorkflowException
     */
    private function isTransitionAllowedForAllWooDecisions(iterable $wooDecisions): void
    {
        foreach ($wooDecisions as $wooDecision) {
            if (! $this->dossierWorkflowManager->isTransitionAllowed($wooDecision, DossierStatusTransition::UPDATE_DOCUMENTS)) {
                throw DossierWorkflowException::forTransitionNotAllowed($wooDecision, DossierStatusTransition::UPDATE_DOCUMENTS);
            }
        }
    }
}
