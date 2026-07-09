<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\NoticeNotPublic\Handler;

use Mockery;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Command\CreateNoticeNotPublicCommand;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Event\NoticeNotPublicCreatedEvent;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\Handler\CreateNoticeNotPublicHandler;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicAlreadyExistsException;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublicRepository;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentAlreadyExistsException;
use Shared\Domain\Search\SearchDispatcher;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\PlainDate;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class CreateNoticeNotPublicHandlerTest extends UnitTestCase
{
    public function testNoticeIsCreatedIfNoneExists(): void
    {
        $dossierRepository = Mockery::mock(DossierRepository::class);
        $repository = Mockery::mock(NoticeNotPublicRepository::class);
        $messageBus = Mockery::mock(MessageBusInterface::class);
        $searchDispatcher = Mockery::mock(SearchDispatcher::class);

        $dossierId = Uuid::v6();
        $formalDate = PlainDate::today();
        $grounds = [Citation::GROUND_WOO_511A, Citation::GROUND_WOO_512A, Citation::GROUND_WOO_512B];
        $documentName = $this->getFaker()->sentence(3);
        $explanation = $this->getFaker()->sentence();

        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getId')
            ->times(3)
            ->andReturn($dossierId);
        $dossier->expects('getMainDocument')
            ->andReturnNull();

        $dossierRepository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturnNull();

        $repository->expects('save')->with(
            Mockery::on(
                static fn (NoticeNotPublic $notice) => $notice->getDossier()->getId() === $dossierId
                    && $notice->getFormalDate() === $formalDate
                    && $notice->getGrounds() === $grounds
                    && $notice->getDocumentName() === $documentName
                    && $notice->getExplanation() === $explanation,
            ),
            true,
        );

        $searchDispatcher->expects('dispatchIndexDossierCommand')
            ->with($dossierId);

        $messageBus->expects('dispatch')
            ->with(Mockery::type(NoticeNotPublicCreatedEvent::class))
            ->andReturns(new Envelope(new stdClass()));

        $handler = new CreateNoticeNotPublicHandler($dossierRepository, $messageBus, $repository, $searchDispatcher);
        $result = $handler->__invoke(
            new CreateNoticeNotPublicCommand(
                $dossierId,
                $documentName,
                $formalDate,
                $grounds,
                $explanation,
            ),
        );

        self::assertInstanceOf(NoticeNotPublic::class, $result);
        self::assertEquals($dossierId, $result->getDossier()->getId());
    }

    public function testExceptionIsThrownWhenNoticeAlreadyExists(): void
    {
        $repository = Mockery::mock(NoticeNotPublicRepository::class);

        $dossierId = Uuid::v6();
        $existingNotice = Mockery::mock(NoticeNotPublic::class);

        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($existingNotice);

        $this->expectException(NoticeNotPublicAlreadyExistsException::class);

        $handler = new CreateNoticeNotPublicHandler(
            Mockery::mock(DossierRepository::class),
            Mockery::mock(MessageBusInterface::class),
            $repository,
            Mockery::mock(SearchDispatcher::class),
        );
        $handler->__invoke(
            new CreateNoticeNotPublicCommand(
                $dossierId,
                $this->getFaker()->word(),
                PlainDate::today(),
                [Citation::GROUND_WOO_511A],
                null,
            ),
        );
    }

    public function testExceptionIsThrownWhenMainDocumentExists(): void
    {
        $dossierId = Uuid::v6();
        $mainDocument = Mockery::mock(AbstractMainDocument::class);

        $repository = Mockery::mock(NoticeNotPublicRepository::class);
        $repository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturnNull();

        $dossier = Mockery::mock(Advice::class);
        $dossier->expects('getMainDocument')
            ->andReturn($mainDocument);

        $dossierRepository = Mockery::mock(DossierRepository::class);
        $dossierRepository->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->expectException(MainDocumentAlreadyExistsException::class);

        $handler = new CreateNoticeNotPublicHandler(
            $dossierRepository,
            Mockery::mock(MessageBusInterface::class),
            $repository,
            Mockery::mock(SearchDispatcher::class),
        );
        $handler->__invoke(
            new CreateNoticeNotPublicCommand(
                $dossierId,
                'test',
                PlainDate::today(),
                [Citation::GROUND_WOO_511A],
                null,
            ),
        );
    }
}
