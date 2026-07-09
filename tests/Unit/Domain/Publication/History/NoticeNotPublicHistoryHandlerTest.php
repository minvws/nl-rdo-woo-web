<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use Mockery;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicCreatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicDeletedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicUpdatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\History\NoticeNotPublicHistoryHandler;
use Shared\Service\HistoryService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class NoticeNotPublicHistoryHandlerTest extends UnitTestCase
{
    public function testHandleCreate(): void
    {
        $dossierId = Uuid::v6();
        $noticeId = Uuid::v6();

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')
            ->times(2)
            ->andReturn($dossierId);
        $dossier->expects('getType')
            ->andReturn(DossierType::COVENANT);

        $notice = Mockery::mock(NoticeNotPublic::class);
        $notice->expects('getId')
            ->andReturn($noticeId);
        $notice->expects('getDossier')
            ->andReturn($dossier);

        $repository = Mockery::mock(DossierRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $event = NoticeNotPublicCreatedEvent::forNotice($notice);

        $historyService = Mockery::mock(HistoryService::class);
        $historyService->expects('addDossierEntry')
            ->with($dossierId, 'covenant.notice_not_public_added');

        $handler = new NoticeNotPublicHistoryHandler($historyService, $repository);
        $handler->handleCreate($event);
    }

    public function testHandleUpdate(): void
    {
        $dossierId = Uuid::v6();
        $noticeId = Uuid::v6();

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')
            ->times(2)
            ->andReturn($dossierId);
        $dossier->expects('getType')
            ->andReturn(DossierType::COVENANT);

        $notice = Mockery::mock(NoticeNotPublic::class);
        $notice->expects('getId')
            ->andReturn($noticeId);
        $notice->expects('getDossier')
            ->andReturn($dossier);

        $repository = Mockery::mock(DossierRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $event = NoticeNotPublicUpdatedEvent::forNotice($notice);

        $historyService = Mockery::mock(HistoryService::class);
        $historyService->expects('addDossierEntry')
            ->with($dossierId, 'covenant.notice_not_public_updated');

        $handler = new NoticeNotPublicHistoryHandler($historyService, $repository);
        $handler->handleUpdate($event);
    }

    public function testHandleDelete(): void
    {
        $dossierId = Uuid::v6();
        $noticeId = Uuid::v6();

        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getId')
            ->times(2)
            ->andReturn($dossierId);
        $dossier->expects('getType')
            ->andReturn(DossierType::COVENANT);

        $notice = Mockery::mock(NoticeNotPublic::class);
        $notice->expects('getId')
            ->andReturn($noticeId);
        $notice->expects('getDossier')
            ->andReturn($dossier);

        $repository = Mockery::mock(DossierRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $event = NoticeNotPublicDeletedEvent::forNotice($notice);

        $historyService = Mockery::mock(HistoryService::class);
        $historyService->expects('addDossierEntry')
            ->with($dossierId, 'covenant.notice_not_public_deleted');

        $handler = new NoticeNotPublicHistoryHandler($historyService, $repository);
        $handler->handleDelete($event);
    }
}
