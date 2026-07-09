<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\NoticeNotPublic\Handler;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\DeleteNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\EntityWithNoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicDeletedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Search\SearchDispatcher;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
readonly class DeleteNoticeNotPublicHandler
{
    public function __construct(
        private DossierRepository $dossierRepository,
        private MessageBusInterface $messageBus,
        private NoticeNotPublicRepository $repository,
        private SearchDispatcher $searchDispatcher,
    ) {
    }

    public function __invoke(DeleteNoticeNotPublicCommand $command): void
    {
        $notice = $this->repository->findOneByDossierId($command->dossierId);
        if ($notice === null) {
            throw new NoticeNotPublicNotFoundException();
        }

        $dossier = $this->dossierRepository->findOneByDossierId($command->dossierId);
        Assert::isInstanceOf($dossier, EntityWithNoticeNotPublic::class);
        $dossier->setNoticeNotPublic(null);

        $event = NoticeNotPublicDeletedEvent::forNotice($notice);

        $this->repository->remove($notice, true);

        $this->searchDispatcher->dispatchIndexDossierCommand($command->dossierId);

        $this->messageBus->dispatch($event);
    }
}
