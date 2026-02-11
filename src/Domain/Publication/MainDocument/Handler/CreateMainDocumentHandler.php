<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentCreatedEvent;
use Shared\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class CreateMainDocumentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private DossierRepository $dossierRepository,
        private ValidatorInterface $validator,
        private EntityUploadStorer $uploadStorer,
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

        /** @var EntityRepository<MainDocumentRepositoryInterface> $documentRepository */
        $documentRepository = $this->entityManager->getRepository($dossier->getMainDocumentEntityClass());
        Assert::isInstanceOf($documentRepository, MainDocumentRepositoryInterface::class);

        $mainDocument = $documentRepository->create($dossier, $command);

        $mainDocument->setInternalReference($command->internalReference);
        $mainDocument->setGrounds($command->grounds);

        $violations = $this->validator->validate($mainDocument);
        if ($violations->count() > 0) {
            throw new ValidationFailedException($mainDocument, $violations);
        }

        $this->uploadStorer->storeUploadForEntityWithSourceTypeAndName($mainDocument, $command->uploadFileReference);

        $documentRepository->save($mainDocument, true);

        $this->messageBus->dispatch(
            MainDocumentCreatedEvent::forDocument($mainDocument)
        );

        return $mainDocument;
    }
}
