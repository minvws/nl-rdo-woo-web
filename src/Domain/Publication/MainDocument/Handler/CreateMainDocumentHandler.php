<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\Handler;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use App\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
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
readonly class CreateMainDocumentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private DossierRepository $dossierRepository,
        private UploaderService $uploaderService,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreateMainDocumentCommand $command): AbstractMainDocument
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithMainDocument $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithMainDocument::class);

        if ($dossier->getMainDocument() !== null) {
            throw new MainDocumentAlreadyExistsException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::UPDATE_MAIN_DOCUMENT);

        /** @var MainDocumentRepositoryInterface $documentRepository */
        $documentRepository = $this->entityManager->getRepository($dossier->getMainDocumentEntityClass());
        Assert::isInstanceOf($documentRepository, MainDocumentRepositoryInterface::class);

        $mainDocument = $documentRepository->create($dossier, $command);

        $mainDocument->setInternalReference($command->internalReference);
        $mainDocument->setGrounds($command->grounds);

        $violations = $this->validator->validate($mainDocument);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($mainDocument, $violations);
        }

        $this->uploaderService->attachFileToEntity(
            $command->uploadFileReference,
            $mainDocument,
            $mainDocument::getUploadGroupId(),
        );

        $documentRepository->save($mainDocument, true);

        $this->messageBus->dispatch(
            MainDocumentCreatedEvent::forDocument($mainDocument)
        );

        return $mainDocument;
    }
}
