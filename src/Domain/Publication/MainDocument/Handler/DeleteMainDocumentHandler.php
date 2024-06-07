<?php

declare(strict_types=1);

namespace App\Domain\Publication\MainDocument\Handler;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use App\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use App\Domain\Publication\MainDocument\EntityWithMainDocument;
use App\Domain\Publication\MainDocument\Event\MainDocumentDeletedEvent;
use App\Domain\Publication\MainDocument\MainDocumentNotFoundException;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use App\Service\Storage\DocumentStorageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class DeleteMainDocumentHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private AbstractDossierRepository $dossierRepository,
        private DocumentStorageService $documentStorage,
    ) {
    }

    public function __invoke(DeleteMainDocumentCommand $command): void
    {
        $dossierId = $command->dossierId;
        /** @var AbstractDossier&EntityWithMainDocument $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithMainDocument::class);

        /** @var MainDocumentRepositoryInterface $documentRepository */
        $documentRepository = $this->entityManager->getRepository($dossier->getMainDocumentEntityClass());
        Assert::isInstanceOf($documentRepository, MainDocumentRepositoryInterface::class);

        $annualReportDocument = $documentRepository->findOneByDossierId($dossier->getId());
        if ($annualReportDocument === null) {
            throw new MainDocumentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT);

        $this->documentStorage->removeFileForEntity($annualReportDocument);

        $event = MainDocumentDeletedEvent::forDocument($annualReportDocument);

        $documentRepository->remove($annualReportDocument, true);
        $dossier->setDocument(null);

        $this->messageBus->dispatch($event);
    }
}
