<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AttachmentDispatcher;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepositoryInterface;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Upload\Process\EntityUploadStorer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class CreateAttachmentHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private AttachmentEntityLoader $entityLoader,
        private AttachmentDispatcher $dispatcher,
        private EntityUploadStorer $uploadStorer,
    ) {
    }

    public function __invoke(CreateAttachmentCommand $command): AbstractAttachment
    {
        $dossier = $this->entityLoader->loadAndValidateDossier(
            $command->dossierId,
            DossierStatusTransition::UPDATE_ATTACHMENT,
        );

        /** @var EntityRepository<AttachmentRepositoryInterface> $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository($dossier->getAttachmentEntityClass());
        Assert::isInstanceOf($attachmentRepository, AttachmentRepositoryInterface::class);
        $entity = $attachmentRepository->create($dossier, $command);

        $entity->setInternalReference($command->internalReference);
        $entity->setGrounds($command->grounds);

        $violations = $this->validator->validate($entity);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity, $violations);
        }

        // Add to the dossier and validate this too, which enforces the max nr of attachments
        $entity->getDossier()->addAttachment($entity);
        $violations = $this->validator->validate($entity->getDossier());
        if ($violations->count() > 0) {
            throw new ValidationFailedException($entity->getDossier(), $violations);
        }

        $attachmentRepository->save($entity);

        $this->uploadStorer->storeUploadForEntityWithSourceTypeAndName($entity, $command->uploadFileReference);

        $attachmentRepository->save($entity, true);

        $this->dispatcher->dispatchAttachmentCreatedEvent($entity);

        return $entity;
    }
}
