<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Handler;

use Shared\Domain\Publication\Dossier\Command\UpdateDossierContentCommand;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateDossierContentHandler
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private DossierService $dossierService,
    ) {
    }

    public function __invoke(UpdateDossierContentCommand $command): void
    {
        $this->dossierWorkflowManager->applyTransition($command->dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->dossierService->validateCompletion($command->dossier);
    }
}
