<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\MainDocument\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Shared\Domain\Publication\MainDocument\Command\DeleteMainDocumentCommand;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\Event\MainDocumentDeletedEvent;
use Shared\Domain\Publication\MainDocument\MainDocumentDeleteStrategyInterface;
use Shared\Domain\Publication\MainDocument\MainDocumentNotFoundException;
use Shared\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class DeleteMainDocumentHandler
{
    /**
     * @param iterable<MainDocumentDeleteStrategyInterface> $deleteStrategies
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        private DossierWorkflowManager $dossierWorkflowManager,
        private EntityManagerInterface $entityManager,
        private DossierRepository $dossierRepository,
        #[AutowireIterator('woo_platform.publication.main_document_delete_strategy')]
        private iterable $deleteStrategies,
    ) {
    }

    public function __invoke(DeleteMainDocumentCommand $command): void
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

        $this->dossierWorkflowManager->applyTransition($dossier, DossierStatusTransition::DELETE_MAIN_DOCUMENT);

        foreach ($this->deleteStrategies as $strategy) {
            $strategy->delete($mainDocument);
        }

        $event = MainDocumentDeletedEvent::forDocument($mainDocument);

        $documentRepository->remove($mainDocument, true);
        $dossier->setMainDocument(null);

        $this->messageBus->dispatch($event);
    }
}
