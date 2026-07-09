<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\Handler;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\CreateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicCreatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicAlreadyExistsException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class CreateNoticeNotPublicHandler
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private MessageBusInterface $messageBus,
        private NoticeNotPublicRepository $repository,
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function __invoke(CreateNoticeNotPublicCommand $command): NoticeNotPublic
    {
        if ($this->repository->findOneByDossierId($command->dossierId) !== null) {
            throw new NoticeNotPublicAlreadyExistsException();
        }

        $dossier = $this->dossierRepository->findOneByDossierId($command->dossierId);

        if ($dossier instanceof EntityWithMainDocument && $dossier->getMainDocument() !== null) {
            throw new MainDocumentAlreadyExistsException();
        }

        $notice = new NoticeNotPublic(
            id: Uuid::v6(),
            dossier: $dossier,
            documentName: $command->documentName,
            formalDate: $command->formalDate,
            grounds: $command->grounds,
            explanation: $command->explanation,
        );

        $this->repository->save($notice, true);

        $this->messageBus->dispatch(NoticeNotPublicCreatedEvent::forNotice($notice));

        $this->searchDispatcher->dispatchIndexDossierCommand($command->dossierId);

        return $notice;
    }
}
