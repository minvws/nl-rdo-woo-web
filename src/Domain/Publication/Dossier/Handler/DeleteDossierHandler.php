<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Handler;

use App\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteDossierHandler
{
    /**
     * @param iterable<DossierDeleteStrategyInterface> $deleteStrategies
     */
    public function __construct(
        private readonly DossierRepository $repository,
        private readonly LoggerInterface $logger,
        private readonly DossierWorkflowManager $dossierWorkflowManager,
        private readonly EntityManagerInterface $entityManager,
        #[AutowireIterator('woo_platform.publication.dossier_delete_strategy')]
        private readonly iterable $deleteStrategies,
    ) {
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

        $this->entityManager->beginTransaction();
        try {
            $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE);

            foreach ($this->deleteStrategies as $strategy) {
                $strategy->delete($dossier);
            }

            $this->repository->remove($dossier);
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
    }
}
