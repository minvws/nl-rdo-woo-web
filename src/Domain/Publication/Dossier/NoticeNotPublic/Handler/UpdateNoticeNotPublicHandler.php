<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\Handler;

use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\UpdateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicUpdatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly class UpdateNoticeNotPublicHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private NoticeNotPublicRepository $repository,
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function __invoke(UpdateNoticeNotPublicCommand $command): NoticeNotPublic
    {
        $notice = $this->repository->findOneByDossierId($command->dossierId);
        if ($notice === null) {
            throw new NoticeNotPublicNotFoundException();
        }

        $notice->setDocumentName($command->documentName);
        $notice->setFormalDate($command->formalDate);
        $notice->setGrounds($command->grounds);
        $notice->setExplanation($command->explanation);

        $this->repository->save($notice, true);

        $this->messageBus->dispatch(NoticeNotPublicUpdatedEvent::forNotice($notice));

        $this->searchDispatcher->dispatchIndexDossierCommand($command->dossierId);

        return $notice;
    }
}
