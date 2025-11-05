<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Workflow;

use App\Domain\Publication\BatchDownload\BatchDownloadScope;
use App\Domain\Publication\BatchDownload\BatchDownloadService;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\DossierService;
use App\Service\HistoryService;
use App\Service\Inquiry\InquiryService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Exception\TransitionException;

/**
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
readonly class DossierWorkflowManager
{
    public function __construct(
        private LoggerInterface $logger,
        private InquiryService $inquiryService,
        private HistoryService $historyService,
        private DossierTypeManager $dossierTypeManager,
        private DossierService $dossierService,
        private BatchDownloadService $batchDownloadService,
    ) {
    }

    public function isTransitionAllowed(AbstractDossier $dossier, DossierStatusTransition $transition): bool
    {
        return $this->dossierTypeManager->getStatusWorkflow($dossier)->can($dossier, $transition->value);
    }

    public function applyTransition(AbstractDossier $dossier, DossierStatusTransition $transition): void
    {
        $oldState = $dossier->getStatus();

        $statusWorkflow = $this->dossierTypeManager->getStatusWorkflow($dossier);

        try {
            $statusWorkflow->apply($dossier, $transition->value);
        } catch (TransitionException $exception) {
            $this->logger->error('Invalid dossier status transition', [
                'dossier' => $dossier->getId(),
                'status' => $dossier->getStatus(),
                'transition' => $transition->value,
                'exception' => $exception,
            ]);

            throw DossierWorkflowException::forTransitionFailed($dossier, $transition, $exception);
        }

        // TODO: below are mostly side-effects that should eventually be loosely coupled using events, see issue #2080
        $this->dossierService->handleEntityUpdate($dossier);
        if ($dossier->getStatus() !== $oldState && $dossier->getStatus()->isPubliclyAvailable() && $dossier instanceof WooDecision) {
            $this->batchDownloadService->refresh(
                BatchDownloadScope::forWooDecision($dossier),
            );

            foreach ($dossier->getInquiries() as $inquiry) {
                $this->inquiryService->generateInventory($inquiry);
            }
        }

        $newState = $dossier->getStatus();

        if ($oldState === DossierStatus::NEW && $newState === DossierStatus::CONCEPT) {
            // No need to log the change from status NEW to CONCEPT as this is just a technicality

            return;
        }

        if ($oldState === $newState) {
            // In some cases applying a transition does not result in an actual status change (a non-moving transition)

            return;
        }

        $this->historyService->addDossierEntry($dossier->getId(), 'dossier_state_' . $newState->value, [
            'old' => '%' . $oldState->value . '%',
            'new' => '%' . $newState->value . '%',
        ]);

        $this->logger->info('Dossier state changed', [
            'dossier' => $dossier->getId(),
            'oldState' => $oldState->value,
            'newState' => $newState->value,
        ]);
    }
}
