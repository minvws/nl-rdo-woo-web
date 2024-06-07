<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class DeleteAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private AbstractDossierRepository $dossierRepository,
        private DocumentStorageService $documentStorage,
    ) {
    }

    public function __invoke(DeleteAttachmentCommand $command): void
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithAttachments $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithAttachments::class);

        /** @var AttachmentRepositoryInterface $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository($dossier->getAttachmentEntityClass());
        Assert::isInstanceOf($attachmentRepository, AttachmentRepositoryInterface::class);
        $entity = $attachmentRepository->findOneOrNullForDossier($dossierId, $command->attachmentId);
        if ($entity === null) {
            throw new AttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_ATTACHMENT);

        $this->documentStorage->removeFileForEntity($entity);

        $event = AttachmentDeletedEvent::forAttachment($entity);

        $attachmentRepository->remove($entity, true);

        $this->messageBus->dispatch($event);
    }
}
