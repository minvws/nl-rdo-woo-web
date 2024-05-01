<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant\Handler\CovenantDocument;

use App\Domain\Publication\Dossier\Type\Covenant\Command\UpdateCovenantDocumentCommand;
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
readonly class UpdateCovenantDocumentHandler
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

    public function __invoke(UpdateCovenantDocumentCommand $command): CovenantDocument
    {
        $dossierId = $command->dossierId;
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);

        $covenantDocument = $this->covenantDocumentRepository->findOneByDossierId($dossier->getId());
        if ($covenantDocument === null) {
            throw new CovenantDocumentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_CONTENT);

        $this->mapProperties($command, $covenantDocument);

        $violations = $this->validator->validate($covenantDocument);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($covenantDocument, $violations);
        }

        $this->mapUpload($command, $covenantDocument);

        $this->covenantDocumentRepository->save($covenantDocument, true);

        $this->messageBus->dispatch(
            new CovenantDocumentUpdatedEvent($covenantDocument)
        );

        return $covenantDocument;
    }

    private function mapProperties(UpdateCovenantDocumentCommand $command, CovenantDocument $covenantDocument): void
    {
        if ($command->formalDate !== null) {
            $covenantDocument->setFormalDate($command->formalDate);
        }

        if ($command->language !== null) {
            $covenantDocument->setLanguage($command->language);
        }

        if ($command->internalReference !== null) {
            $covenantDocument->setInternalReference($command->internalReference);
        }

        if ($command->grounds !== null) {
            $covenantDocument->setGrounds($command->grounds);
        }

        if ($command->name !== null) {
            $covenantDocument->getFileInfo()->setName($command->name);
        }
    }

    private function mapUpload(UpdateCovenantDocumentCommand $command, CovenantDocument $covenantDocument): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploaderService->attachFileToEntity(
                $command->uploadFileReference,
                $covenantDocument,
                $covenantDocument->getUploadGroupId(),
            );
        }
    }
}
