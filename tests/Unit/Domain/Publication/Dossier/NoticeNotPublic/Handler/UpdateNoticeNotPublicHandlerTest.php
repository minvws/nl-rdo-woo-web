<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\NoticeNotPublic\Handler;

use Mockery;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\UpdateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicUpdatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Handler\UpdateNoticeNotPublicHandler;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicNotFoundException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class UpdateNoticeNotPublicHandlerTest extends UnitTestCase
{
    public function testNoticeIsUpdated(): void
    {
        $dossierId = Uuid::v6();
        $noticeId = Uuid::v6();
        $formalDate = PlainDate::today();
        $grounds = [Citation::GROUND_WOO_511A, Citation::GROUND_WOO_512A, Citation::GROUND_WOO_512B];
        $documentName = $this->getFaker()->sentence(2);
        $explanation = $this->getFaker()->sentence();

        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getId')
            ->times(1)
            ->andReturn($dossierId);

        $notice = Mockery::mock(NoticeNotPublic::class);
        $notice->expects('getId')
            ->andReturn($noticeId);
        $notice->expects('getDossier')
            ->andReturn($dossier);
        $notice->expects('setDocumentName')
            ->with($documentName);
        $notice->expects('setFormalDate')
            ->with($formalDate);
        $notice->expects('setGrounds')
            ->with($grounds);
        $notice->expects('setExplanation')
            ->with($explanation);

        $repository = Mockery::mock(NoticeNotPublicRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($notice);
        $repository->expects('save')
            ->with($notice, true);

        $messageBus = Mockery::mock(MessageBusInterface::class);
        $messageBus->expects('dispatch')
            ->with(Mockery::type(NoticeNotPublicUpdatedEvent::class))
            ->andReturns(new Envelope(new stdClass()));

        $searchDispatcher = Mockery::mock(SearchDispatcher::class);
        $searchDispatcher->expects('dispatchIndexDossierCommand')
            ->with($dossierId);

        $handler = new UpdateNoticeNotPublicHandler($messageBus, $repository, $searchDispatcher);
        $handler->__invoke(
            new UpdateNoticeNotPublicCommand(
                $dossierId,
                $documentName,
                $formalDate,
                $grounds,
                $explanation,
            ),
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

        $handler = new UpdateNoticeNotPublicHandler(
            Mockery::mock(MessageBusInterface::class),
            $repository,
            Mockery::mock(SearchDispatcher::class),
        );
        $handler->__invoke(
            new UpdateNoticeNotPublicCommand(
                $dossierId,
                $this->getFaker()->word(),
                PlainDate::today(),
                [Citation::GROUND_WOO_511A],
                null,
            ),
        );
    }
}
