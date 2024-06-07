<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\Handler;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\UpdateMainDocumentCommand;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use App\Domain\Publication\MainDocument\MainDocumentNotFoundException;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
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
readonly class UpdateMainDocumentHandler
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

    public function __invoke(UpdateMainDocumentCommand $command): AbstractMainDocument
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithMainDocument $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithMainDocument::class);

        /** @var MainDocumentRepositoryInterface $documentRepository */
        $documentRepository = $this->entityManager->getRepository($dossier->getMainDocumentEntityClass());
        Assert::isInstanceOf($documentRepository, MainDocumentRepositoryInterface::class);

        $mainDocument = $documentRepository->findOneByDossierId($dossier->getId());
        if ($mainDocument === null) {
            throw new MainDocumentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        $this->mapProperties($command, $mainDocument);

        $violations = $this->validator->validate($mainDocument);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($mainDocument, $violations);
        }

        $this->mapUpload($command, $mainDocument);

        $documentRepository->save($mainDocument, true);

        $this->messageBus->dispatch(
            MainDocumentUpdatedEvent::forDocument($mainDocument),
        );

        return $mainDocument;
    }

    private function mapProperties(UpdateMainDocumentCommand $command, AbstractMainDocument $mainDocument): void
    {
        if ($command->formalDate !== null) {
            $mainDocument->setFormalDate($command->formalDate);
        }

        if ($command->language !== null) {
            $mainDocument->setLanguage($command->language);
        }

        if ($command->internalReference !== null) {
            $mainDocument->setInternalReference($command->internalReference);
        }

        if ($command->type !== null) {
            $mainDocument->setType($command->type);
        }

        if ($command->grounds !== null) {
            $mainDocument->setGrounds($command->grounds);
        }

        if ($command->name !== null) {
            $mainDocument->getFileInfo()->setName($command->name);
        }
    }

    private function mapUpload(UpdateMainDocumentCommand $command, AbstractMainDocument $mainDocument): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploaderService->attachFileToEntity(
                $command->uploadFileReference,
                $mainDocument,
                $mainDocument->getUploadGroupId(),
            );
        }
    }
}
