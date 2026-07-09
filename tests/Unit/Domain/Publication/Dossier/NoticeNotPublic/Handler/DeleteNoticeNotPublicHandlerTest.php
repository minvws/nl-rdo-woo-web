<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\NoticeNotPublic\Handler;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\DeleteNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicDeletedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Handler\DeleteNoticeNotPublicHandler;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class DeleteNoticeNotPublicHandlerTest extends UnitTestCase
{
    public function testNoticeIsDeleted(): void
    {
        $dossierId = Uuid::v6();
        $noticeId = Uuid::v6();

        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getId')
            ->andReturn($dossierId);
        $dossier->expects('setNoticeNotPublic')
            ->with(null);

        $notice = Mockery::mock(NoticeNotPublic::class);
        $notice->expects('getId')
            ->andReturn($noticeId);
        $notice->expects('getDossier')
            ->andReturn($dossier);

        $repository = Mockery::mock(NoticeNotPublicRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($notice);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $repository->expects('remove')
            ->with($notice, true);

        $searchDispatcher = Mockery::mock(SearchDispatcher::class);
        $searchDispatcher->expects('dispatchIndexDossierCommand')
            ->with($dossierId);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::on(
                static fn (NoticeNotPublicDeletedEvent $event) => $event->noticeId === $noticeId && $event->dossierId === $dossierId,
            ))
            ->andReturns(new Envelope(new stdClass()));

        $handler = new DeleteNoticeNotPublicHandler($dossierRepository, $messageBus, $repository, $searchDispatcher);
        $handler->__invoke(
            new DeleteNoticeNotPublicCommand($dossierId),
        );
    }

    public function testExceptionIsThrownWhenNoticeCannotBeFound(): void
    {
        $dossierId = Uuid::v6();

        $repository = Mockery::mock(NoticeNotPublicRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturnNull();

        $this->expectException(NoticeNotPublicNotFoundException::class);

        $handler = new DeleteNoticeNotPublicHandler(
            Mockery::mock(DossierRepository::class),
            Mockery::mock(MessageBusInterface::class),
            $repository,
            Mockery::mock(SearchDispatcher::class),
        );
        $handler->__invoke(
            new DeleteNoticeNotPublicCommand($dossierId),
        );
    }
}
