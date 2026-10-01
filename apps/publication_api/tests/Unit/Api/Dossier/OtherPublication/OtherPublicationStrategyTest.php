<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use Mockery;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationAttachmentSynchronizer;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationPersister;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationRequestMapper;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationSnapshot;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationStrategy;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class OtherPublicationStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $otherPublicationStrategy = new OtherPublicationStrategy(
            Mockery::mock(OtherPublicationPersister::class),
            Mockery::mock(OtherPublicationRequestMapper::class),
            Mockery::mock(OtherPublicationAttachmentSynchronizer::class),
            Mockery::mock(OtherPublicationMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame(OtherPublication::class, $otherPublicationStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
        );
        $otherPublicationRequestDto = new OtherPublicationRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
            null,
        );

        $otherPublication = new OtherPublication();
        $attachmentEvents = [];

        $otherPublicationRequestMapper = Mockery::mock(OtherPublicationRequestMapper::class);
        $otherPublicationRequestMapper->expects('create')->with(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($otherPublication);

        $otherPublicationMainDocumentSynchronizer = Mockery::mock(OtherPublicationMainDocumentSynchronizer::class);
        $otherPublicationMainDocumentSynchronizer->expects('create')->with($otherPublication, $mainDocumentRequestDto);

        $otherPublicationAttachmentSynchronizer = Mockery::mock(OtherPublicationAttachmentSynchronizer::class);
        $otherPublicationAttachmentSynchronizer->expects('create')
            ->with($otherPublication, $otherPublicationRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $otherPublicationPersister = Mockery::mock(OtherPublicationPersister::class);
        $otherPublicationPersister->expects('persist')->with($otherPublication, null, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $otherPublicationPersister,
            $otherPublicationRequestMapper,
            $otherPublicationAttachmentSynchronizer,
            $otherPublicationMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame($otherPublication, $otherPublicationStrategy->create(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        ));
    }

    public function testCreateWithNoticeNotPublic(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);

        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );
        $otherPublicationRequestDto = new OtherPublicationRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $otherPublication = new OtherPublication();
        $attachmentEvents = [];

        $otherPublicationRequestMapper = Mockery::mock(OtherPublicationRequestMapper::class);
        $otherPublicationRequestMapper->expects('create')->andReturn($otherPublication);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($otherPublication, $noticeNotPublicRequestDto);

        $otherPublicationAttachmentSynchronizer = Mockery::mock(OtherPublicationAttachmentSynchronizer::class);
        $otherPublicationAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $otherPublicationPersister = Mockery::mock(OtherPublicationPersister::class);
        $otherPublicationPersister->expects('persist')->with($otherPublication, null, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $otherPublicationPersister,
            $otherPublicationRequestMapper,
            $otherPublicationAttachmentSynchronizer,
            Mockery::mock(OtherPublicationMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        self::assertSame($otherPublication, $otherPublicationStrategy->create(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            null,
            $dossierExternalId,
            $documentPrefix,
        ));
    }

    public function testUpdateWithMainDocumentRemovesNoticeNotPublic(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new OtherPublicationMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
        );
        $otherPublicationRequestDto = new OtherPublicationRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
            null,
        );

        $otherPublication = new OtherPublication();
        $otherPublicationSnapshot = OtherPublicationSnapshot::of($otherPublication);
        $attachmentEvents = [];

        $otherPublicationRequestMapper = Mockery::mock(OtherPublicationRequestMapper::class);
        $otherPublicationRequestMapper->expects('update')->with($otherPublication, $otherPublicationRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($otherPublication);

        $otherPublicationMainDocumentSynchronizer = Mockery::mock(OtherPublicationMainDocumentSynchronizer::class);
        $otherPublicationMainDocumentSynchronizer->expects('update')->with($otherPublication, $mainDocumentRequestDto);

        $otherPublicationAttachmentSynchronizer = Mockery::mock(OtherPublicationAttachmentSynchronizer::class);
        $otherPublicationAttachmentSynchronizer->expects('update')
            ->with($otherPublication, $otherPublicationRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $otherPublicationPersister = Mockery::mock(OtherPublicationPersister::class);
        $otherPublicationPersister->expects('snapshot')->with($otherPublication)->andReturn($otherPublicationSnapshot);
        $otherPublicationPersister->expects('persist')->with($otherPublication, $otherPublicationSnapshot, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $otherPublicationPersister,
            $otherPublicationRequestMapper,
            $otherPublicationAttachmentSynchronizer,
            $otherPublicationMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $otherPublicationStrategy->update($otherPublication, $otherPublicationRequestDto, $organisation, $department, $subject);
    }

    public function testUpdateWithNoticeNotPublicRemovesMainDocument(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);

        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );
        $otherPublicationRequestDto = new OtherPublicationRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $otherPublication = new OtherPublication();
        $otherPublicationSnapshot = OtherPublicationSnapshot::of($otherPublication);
        $attachmentEvents = [];

        $otherPublicationRequestMapper = Mockery::mock(OtherPublicationRequestMapper::class);
        $otherPublicationRequestMapper->expects('update')->with($otherPublication, $otherPublicationRequestDto, $organisation, $department, null);

        $otherPublicationMainDocumentSynchronizer = Mockery::mock(OtherPublicationMainDocumentSynchronizer::class);
        $otherPublicationMainDocumentSynchronizer->expects('delete')->with($otherPublication);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($otherPublication, $noticeNotPublicRequestDto);

        $otherPublicationAttachmentSynchronizer = Mockery::mock(OtherPublicationAttachmentSynchronizer::class);
        $otherPublicationAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $otherPublicationPersister = Mockery::mock(OtherPublicationPersister::class);
        $otherPublicationPersister->expects('snapshot')->with($otherPublication)->andReturn($otherPublicationSnapshot);
        $otherPublicationPersister->expects('persist')->with($otherPublication, $otherPublicationSnapshot, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $otherPublicationPersister,
            $otherPublicationRequestMapper,
            $otherPublicationAttachmentSynchronizer,
            $otherPublicationMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $otherPublicationStrategy->update($otherPublication, $otherPublicationRequestDto, $organisation, $department, null);
    }
}
