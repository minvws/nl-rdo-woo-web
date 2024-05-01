<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

use App\Domain\Publication\Dossier\Type\Covenant\Command\DeleteCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantDocumentDeletedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Repository\CovenantRepository;
use App\Service\Storage\DocumentStorageService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class DeleteCovenantDocumentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private CovenantDocumentRepository $covenantDocumentRepository,
        private CovenantRepository $dossierRepository,
        private DocumentStorageService $documentStorage,
    ) {
    }

    public function __invoke(DeleteCovenantDocumentCommand $command): void
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $covenantDocument = $this->covenantDocumentRepository->findOneByDossierId($dossier->getId());
        if ($covenantDocument === null) {
            throw new CovenantDocumentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_COVENANT_DOCUMENT);

        $this->documentStorage->removeFileForEntity($covenantDocument);
        $this->covenantDocumentRepository->remove($covenantDocument, true);

        $this->messageBus->dispatch(
            new CovenantDocumentDeletedEvent($covenantDocument->getId(), $dossier)
        );
    }
}
