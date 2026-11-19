<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\MainDocumentRequestMapperInterface;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Ground;
use Shared\Tests\Unit\UnitTestCase;
use Webmozart\Assert\InvalidArgumentException;

use function array_map;

final class DossierMainDocumentOrNoticeSynchronizerTest extends UnitTestCase
{
    public function testCreateWithMainDocument(): void
    {
        $covenant = new Covenant();
        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $mainDocumentRequestMapper = Mockery::mock(MainDocumentRequestMapperInterface::class);

        $dossierMainDocumentSynchronizer = Mockery::mock(DossierMainDocumentSynchronizer::class);
        $dossierMainDocumentSynchronizer->expects('create')->with($covenant, $covenantMainDocumentRequestDto, $mainDocumentRequestMapper);

        $dossierMainDocumentOrNoticeSynchronizer = new DossierMainDocumentOrNoticeSynchronizer(
            $dossierMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        $dossierMainDocumentOrNoticeSynchronizer->create($covenant, $covenantMainDocumentRequestDto, null, $mainDocumentRequestMapper);
    }

    public function testCreateWithNoticeNotPublic(): void
    {
        $covenant = new Covenant();
        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($covenant, $noticeNotPublicRequestDto);

        $dossierMainDocumentOrNoticeSynchronizer = new DossierMainDocumentOrNoticeSynchronizer(
            Mockery::mock(DossierMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        $dossierMainDocumentOrNoticeSynchronizer->create(
            $covenant,
            null,
            $noticeNotPublicRequestDto,
            Mockery::mock(MainDocumentRequestMapperInterface::class),
        );
    }

    public function testCreateWithoutMainDocumentAndNoticeNotPublic(): void
    {
        $dossierMainDocumentOrNoticeSynchronizer = new DossierMainDocumentOrNoticeSynchronizer(
            Mockery::mock(DossierMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::expectException(InvalidArgumentException::class);
        $dossierMainDocumentOrNoticeSynchronizer->create(
            new Covenant(),
            null,
            null,
            Mockery::mock(MainDocumentRequestMapperInterface::class),
        );
    }

    public function testUpdateWithMainDocumentRemovesNoticeNotPublic(): void
    {
        $covenant = new Covenant();
        $covenantMainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $mainDocumentRequestMapper = Mockery::mock(MainDocumentRequestMapperInterface::class);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($covenant);

        $dossierMainDocumentSynchronizer = Mockery::mock(DossierMainDocumentSynchronizer::class);
        $dossierMainDocumentSynchronizer->expects('update')->with($covenant, $covenantMainDocumentRequestDto, $mainDocumentRequestMapper);

        $dossierMainDocumentOrNoticeSynchronizer = new DossierMainDocumentOrNoticeSynchronizer(
            $dossierMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $dossierMainDocumentOrNoticeSynchronizer->update($covenant, $covenantMainDocumentRequestDto, null, $mainDocumentRequestMapper);
    }

    public function testUpdateWithNoticeNotPublicRemovesMainDocument(): void
    {
        $covenant = new Covenant();
        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );

        $dossierMainDocumentSynchronizer = Mockery::mock(DossierMainDocumentSynchronizer::class);
        $dossierMainDocumentSynchronizer->expects('delete')->with($covenant);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($covenant, $noticeNotPublicRequestDto);

        $dossierMainDocumentOrNoticeSynchronizer = new DossierMainDocumentOrNoticeSynchronizer(
            $dossierMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $dossierMainDocumentOrNoticeSynchronizer->update(
            $covenant,
            null,
            $noticeNotPublicRequestDto,
            Mockery::mock(MainDocumentRequestMapperInterface::class),
        );
    }

    public function testUpdateWithoutMainDocumentAndNoticeNotPublic(): void
    {
        $covenant = new Covenant();

        $dossierMainDocumentSynchronizer = Mockery::mock(DossierMainDocumentSynchronizer::class);
        $dossierMainDocumentSynchronizer->expects('delete')->with($covenant);

        $dossierMainDocumentOrNoticeSynchronizer = new DossierMainDocumentOrNoticeSynchronizer(
            $dossierMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::expectException(InvalidArgumentException::class);
        $dossierMainDocumentOrNoticeSynchronizer->update(
            $covenant,
            null,
            null,
            Mockery::mock(MainDocumentRequestMapperInterface::class),
        );
    }
}
