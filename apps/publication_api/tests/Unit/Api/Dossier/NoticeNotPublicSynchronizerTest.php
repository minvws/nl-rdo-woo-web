<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use Mockery;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicService;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class NoticeNotPublicSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $documentName = $this->getFaker()->sentence();
        $formalDate = $this->getFaker()->plainDate();
        $grounds = $this->getFaker()->grounds();
        $explanation = $this->getFaker()->sentence();

        $covenant = new Covenant();
        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto($formalDate, $documentName, array_map(Ground::from(...), $grounds), $explanation);

        $noticeNotPublicSynchronizer = new NoticeNotPublicSynchronizer(
            Mockery::mock(NoticeNotPublicService::class),
        );
        $noticeNotPublicSynchronizer->create($covenant, $noticeNotPublicRequestDto);

        $noticeNotPublic = $covenant->getNoticeNotPublic();
        self::assertInstanceOf(NoticeNotPublic::class, $noticeNotPublic);
        self::assertSame($documentName, $noticeNotPublic->getDocumentName());
        self::assertSame($formalDate, $noticeNotPublic->getFormalDate());
        self::assertSame($grounds, $noticeNotPublic->getGrounds());
        self::assertSame($explanation, $noticeNotPublic->getExplanation());
    }

    public function testUpdateCreatesTheNoticeWhenTheCovenantHasNone(): void
    {
        $covenant = new Covenant();
        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );
        $noticeNotPublic = Mockery::mock(NoticeNotPublic::class);

        $noticeNotPublicService = Mockery::mock(NoticeNotPublicService::class);
        $noticeNotPublicService->expects('createForDossier')->with($covenant, $noticeNotPublicRequestDto)->andReturn($noticeNotPublic);

        $noticeNotPublicSynchronizer = new NoticeNotPublicSynchronizer($noticeNotPublicService);
        $noticeNotPublicSynchronizer->update($covenant, $noticeNotPublicRequestDto);

        self::assertSame($noticeNotPublic, $covenant->getNoticeNotPublic());
    }

    public function testUpdate(): void
    {
        $covenant = new Covenant();
        $covenant->setNoticeNotPublic(
            new NoticeNotPublic(
                Uuid::v6(),
                $covenant,
                $this->getFaker()->sentence(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->grounds(),
                null,
            ),
        );

        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );
        $updatedNoticeNotPublic = Mockery::mock(NoticeNotPublic::class);

        $noticeNotPublicService = Mockery::mock(NoticeNotPublicService::class);
        $noticeNotPublicService->expects('updateForDossier')->with($covenant, $noticeNotPublicRequestDto)->andReturn($updatedNoticeNotPublic);

        $noticeNotPublicSynchronizer = new NoticeNotPublicSynchronizer($noticeNotPublicService);
        $noticeNotPublicSynchronizer->update($covenant, $noticeNotPublicRequestDto);

        self::assertSame($updatedNoticeNotPublic, $covenant->getNoticeNotPublic());
    }

    public function testDelete(): void
    {
        $covenant = new Covenant();
        $covenant->setNoticeNotPublic(
            new NoticeNotPublic(
                Uuid::v6(),
                $covenant,
                $this->getFaker()->sentence(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->grounds(),
                null,
            ),
        );

        $noticeNotPublicService = Mockery::mock(NoticeNotPublicService::class);
        $noticeNotPublicService->expects('deleteFromDossier')->with($covenant);

        $noticeNotPublicSynchronizer = new NoticeNotPublicSynchronizer($noticeNotPublicService);
        $noticeNotPublicSynchronizer->delete($covenant);
    }

    public function testDeleteWithoutNotice(): void
    {
        $covenant = new Covenant();

        $noticeNotPublicSynchronizer = new NoticeNotPublicSynchronizer(
            Mockery::mock(NoticeNotPublicService::class),
        );
        $noticeNotPublicSynchronizer->delete($covenant);

        self::assertNull($covenant->getNoticeNotPublic());
    }
}
