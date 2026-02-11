<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MinVWS\AuditLogger\AuditLogger;
use Psr\Log\LoggerInterface;
use Shared\Domain\Publication\Dossier\AuditLog\DossierDeleteLogEvent;
use Shared\Domain\Publication\Dossier\Command\DeleteDossierCommand;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Type\DossierDeleteStrategyInterface;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

use function sprintf;

#[AsMessageHandler]
readonly class DeleteDossierHandler
{
    /**
     * @param iterable<DossierDeleteStrategyInterface> $deleteStrategies
     */
    public function __construct(
        private DossierRepository $repository,
        private LoggerInterface $logger,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        #[AutowireIterator('woo_platform.publication.dossier_delete_strategy')]
        private iterable $deleteStrategies,
        private AuditLogger $auditLogger,
    ) {
    }

    public function __invoke(DeleteDossierCommand $command): void
    {
        $dossier = $this->repository->find($command->dossierId);
        if ($dossier === null) {
            $this->logger->warning('No dossier found for deletion', [
                'uuid' => $command->dossierId->toRfc4122(),
            ]);

            return;
        }

        $failed = false;
        $failureReason = '';

        $this->entityManager->beginTransaction();
        try {
            if (! $command->overrideWorkflow) {
                $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE);
            }

            foreach ($this->deleteStrategies as $strategy) {
                try {
                    if ($command->overrideWorkflow) {
                        $strategy->deleteWithOverride($dossier);
                    } else {
                        $strategy->delete($dossier);
                    }
                } catch (Exception $exception) {
                    $this->logger->error(
                        sprintf('Error while deleting dossier in strategy %s', $strategy::class),
                        [
                            'message' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'trace' => $exception->getTraceAsString(),
                        ],
                    );

                    throw $exception;
                }
            }

            $this->repository->remove($dossier);
            $this->entityManager->commit();
        } catch (Exception $exception) {
            $this->entityManager->rollback();

            $failed = true;
            $failureReason = $exception->getMessage();

            throw $exception;
        } finally {
            $this->auditLogger->log((new DossierDeleteLogEvent())
                ->asDelete()
                ->withActor($command->auditUserDetails)
                ->withSource('woo')
                ->withData([
                    'id' => $dossier->getId()->toRfc4122(),
                    'prefix' => $dossier->getDocumentPrefix(),
                    'dossier_nr' => $dossier->getDossierNr(),
                    'title' => $dossier->getTitle(),
                    'status' => $dossier->getStatus()->value,
                ])
                ->withFailed($failed, $failureReason));
        }
    }
}
