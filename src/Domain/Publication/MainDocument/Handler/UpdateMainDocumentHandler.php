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
use Shared\Domain\Publication\MainDocument\Command\UpdateMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentUpdatedEvent;
use Shared\Domain\Publication\MainDocument\MainDocumentNotFoundException;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Shared\Domain\Upload\Process\EntityUploadStorer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class UpdateMainDocumentHandler
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

    public function __invoke(UpdateMainDocumentCommand $command): AbstractMainDocument
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithMainDocument $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithMainDocument::class);

        /** @var EntityRepository<MainDocumentRepositoryInterface> $documentRepository */
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
    }

    private function mapUpload(UpdateMainDocumentCommand $command, AbstractMainDocument $mainDocument): void
    {
        if ($command->uploadFileReference !== null) {
            $this->uploadStorer->storeUploadForEntityWithSourceTypeAndName(
                $mainDocument,
                $command->uploadFileReference
            );
        }
    }
}
