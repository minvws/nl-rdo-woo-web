<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantAttachment;

use App\Domain\Publication\Dossier\Type\Covenant\Command\DeleteCovenantAttachmentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachmentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantAttachmentDeletedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Repository\CovenantRepository;
use App\Service\Storage\DocumentStorageService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class DeleteCovenantAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private CovenantAttachmentRepository $covenantAttachmentRepository,
        private CovenantRepository $dossierRepository,
        private DocumentStorageService $documentStorage,
    ) {
    }

    public function __invoke(DeleteCovenantAttachmentCommand $command): void
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $entity = $this->covenantAttachmentRepository->findOneOrNullForDossier($dossierId, $command->attachmentId);
        if ($entity === null) {
            throw new CovenantAttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_COVENANT_ATTACHMENT);

        $this->documentStorage->removeFileForEntity($entity);
        $this->covenantAttachmentRepository->remove($entity, true);

        $this->messageBus->dispatch(
            new CovenantAttachmentDeletedEvent($dossier->getId(), $entity->getId())
        );
    }
}
