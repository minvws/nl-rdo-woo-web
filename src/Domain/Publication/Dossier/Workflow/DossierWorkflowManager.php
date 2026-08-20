<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Workflow;

use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Symfony\Component\Workflow\Exception\TransitionException;

readonly class DossierWorkflowManager
{
    public function __construct(
        private LoggerInterface $logger,
        private DossierTypeManager $dossierTypeManager,
    ) {
    }

    public function isTransitionAllowed(AbstractDossier $dossier, DossierStatusTransition $transition): bool
    {
        return $this->dossierTypeManager->getStatusWorkflow($dossier)->can($dossier, $transition->value);
    }

    public function applyTransition(AbstractDossier $dossier, DossierStatusTransition $transition): void
    {
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
    }
}
