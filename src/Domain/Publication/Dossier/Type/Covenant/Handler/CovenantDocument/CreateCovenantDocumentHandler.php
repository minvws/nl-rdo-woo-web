<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

use App\Domain\Publication\Dossier\Type\Covenant\Command\CreateCovenantDocumentCommand;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocumentRepository;
use App\Domain\Publication\Dossier\Type\Covenant\Event\CovenantDocumentUpdatedEvent;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Repository\CovenantRepository;
use App\Service\Uploader\UploaderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
readonly class CreateCovenantDocumentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private CovenantDocumentRepository $covenantDocumentRepository,
        private CovenantRepository $dossierRepository,
        private UploaderService $uploaderService,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateCovenantDocumentCommand $command): CovenantDocument
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        if ($dossier->getDocument() !== null) {
            throw new CovenantDocumentAlreadyExistsException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $covenantDocument = new CovenantDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            language: $command->language,
        );

        $covenantDocument->setInternalReference($command->internalReference);
        $covenantDocument->setGrounds($command->grounds);
        $covenantDocument->getFileInfo()->setName($command->name);

        $violations = $this->validator->validate($covenantDocument);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($covenantDocument, $violations);
        }

        $this->uploaderService->attachFileToEntity(
            $command->uploadFileReference,
            $covenantDocument,
            $covenantDocument->getUploadGroupId(),
        );

        $this->covenantDocumentRepository->save($covenantDocument, true);

        $this->messageBus->dispatch(
            new CovenantDocumentUpdatedEvent($covenantDocument)
        );

        return $covenantDocument;
    }
}
