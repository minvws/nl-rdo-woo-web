<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Handler;

use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Publication\Dossier\Command\UpdateDossierPublicationCommand;
use Shared\Domain\Publication\Dossier\DossierPublisher;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowException;
use Shared\Service\DossierService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateDossierPublicationHandler
{
    public function __construct(
        private DossierService $dossierService,
        private DossierPublisher $publisher,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateDossierPublicationCommand $command): void
    {
        $dossier = $command->dossier;
        $this->logger->debug('UpdateDossierPublicationCommand triggered', [
            'dossierId' => $dossier->getId(),
        ]);

        $this->dossierService->validateCompletion($dossier, false);

        try {
            match (true) {
                $this->publisher->canPublish($dossier) => $this->publisher->publish($dossier),
                $this->publisher->canPublishAsPreview($dossier) => $this->publisher->publishAsPreview($dossier),
                $this->publisher->canSchedulePublication($dossier) => $this->publisher->schedulePublication($dossier),
                default => throw DossierWorkflowException::forCannotUpdatePublication($dossier),
            };
        } catch (RuntimeException $exception) {
            $this->logger->debug('UpdateDossierPublicationCommand failed to publish dossier', [
                'dossierId' => $dossier->getId(),
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        $this->dossierService->validateCompletion($dossier);
    }
}
