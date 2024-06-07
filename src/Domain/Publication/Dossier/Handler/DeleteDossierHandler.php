<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteDossierHandler
{
    /**
     * @var iterable<DossierDeleteStrategyInterface>
     */
    private iterable $deleteStrategies;

    /**
     * @param iterable<DossierDeleteStrategyInterface> $deleteStrategies
     */
    public function __construct(
        private readonly AbstractDossierRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        iterable $deleteStrategies,
    ) {
        $this->deleteStrategies = $deleteStrategies;
    }

    public function __invoke(DeleteDossierCommand $command): void
    {
        $dossier = $this->repository->find($command->getUuid());
        if ($dossier === null) {
            $this->logger->warning('No dossier found for deletion', [
                'uuid' => $command->getUuid(),
            ]);

            return;
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE);

        foreach ($this->deleteStrategies as $strategy) {
            $strategy->delete($dossier);
        }

        $this->repository->remove($dossier);
    }
}
