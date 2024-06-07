<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\UpdateAttachmentCommand;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\Event\AttachmentUpdatedEvent;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Service\Uploader\UploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[AsMessageHandler]
readonly class UpdateAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private AbstractDossierRepository $dossierRepository,
        private ValidatorInterface $validator,
        private UploaderService $uploaderService,
    ) {
    }

    public function __invoke(UpdateAttachmentCommand $command): AbstractAttachment
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

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_ATTACHMENT);

        $this->mapProperties($command, $entity);

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        $this->mapUpload($command, $entity);

        $attachmentRepository->save($entity, true);

        $this->messageBus->dispatch(
            AttachmentUpdatedEvent::forAttachment($entity),
        );

        return $entity;
    }

    private function mapProperties(UpdateAttachmentCommand $command, AbstractAttachment $entity): void
    {
        if ($command->formalDate !== null) {
            $entity->setFormalDate($command->formalDate);
        }

        if ($command->type !== null) {
            $entity->setType($command->type);
        }

        if ($command->name !== null) {
            $entity->getFileInfo()->setName($command->name);
        }

        if ($command->language !== null) {
            $entity->setLanguage($command->language);
        }

        if ($command->internalReference !== null) {
            $entity->setInternalReference($command->internalReference);
        }

        if ($command->grounds !== null) {
            $entity->setGrounds($command->grounds);
        }

        if ($command->name !== null) {
            $entity->getFileInfo()->setName($command->name);
        }
    }

    private function mapUpload(UpdateAttachmentCommand $command, AbstractAttachment $entity): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploaderService->attachFileToEntity(
                $command->uploadFileReference,
                $entity,
                $entity->getUploadGroupId(),
            );
        }
    }
}
