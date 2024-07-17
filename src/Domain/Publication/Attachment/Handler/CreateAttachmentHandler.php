<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\Event\AttachmentCreatedEvent;
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
readonly class CreateAttachmentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private AbstractDossierRepository $dossierRepository,
        private UploaderService $uploaderService,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateAttachmentCommand $command): AbstractAttachment
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithAttachments $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithAttachments::class);

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_ATTACHMENT);

        /** @var AttachmentRepositoryInterface $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository($dossier->getAttachmentEntityClass());
        Assert::isInstanceOf($attachmentRepository, AttachmentRepositoryInterface::class);
        $entity = $attachmentRepository->create($dossier, $command);

        $entity->setInternalReference($command->internalReference);
        $entity->setGrounds($command->grounds);
        $entity->getFileInfo()->setName($command->name);

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        $this->uploaderService->attachFileToEntity(
            $command->uploadFileReference,
            $entity,
            $entity::getUploadGroupId(),
        );

        $attachmentRepository->save($entity, true);

        $this->messageBus->dispatch(
            AttachmentCreatedEvent::forAttachment($entity),
        );

        return $entity;
    }
}
