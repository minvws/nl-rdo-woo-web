<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\AbstractAttachmentRepository;
use App\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use App\Domain\Publication\Attachment\Command\DeleteAttachmentCommand;
use App\Domain\Publication\Attachment\EntityWithAttachments;
use App\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class DeleteAttachmentHandler
{
    /**
     * @var iterable<AttachmentDeleteStrategyInterface>
     */
    private iterable $deleteStrategies;

    /**
     * @param iterable<AttachmentDeleteStrategyInterface> $deleteStrategies
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private AbstractAttachmentRepository $attachmentRepository,
        private AbstractDossierRepository $dossierRepository,
        iterable $deleteStrategies,
    ) {
        $this->deleteStrategies = $deleteStrategies;
    }

    public function __invoke(DeleteAttachmentCommand $command): void
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithAttachments $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithAttachments::class);

        // TODO use AbstractAttachmentRepository and same in CreateAttachmentHandler
        $entity = $this->attachmentRepository->findOneOrNullForDossier($dossierId, $command->attachmentId);
        if ($entity === null) {
            throw new AttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_ATTACHMENT);

        foreach ($this->deleteStrategies as $strategy) {
            $strategy->delete($entity);
        }

        $event = AttachmentDeletedEvent::forAttachment($entity);

        $this->attachmentRepository->remove($entity, true);

        if ($dossier->getStatus()->isNotDeleted()) {
            $this->messageBus->dispatch($event);
        }
    }
}
