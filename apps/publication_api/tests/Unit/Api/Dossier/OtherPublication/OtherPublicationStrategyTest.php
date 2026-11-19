<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use Mockery;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationRequestMapper;
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
            Mockery::mock(DossierPersister::class),
            Mockery::mock(OtherPublicationRequestMapper::class),
            Mockery::mock(DossierAttachmentSynchronizer::class),
            Mockery::mock(OtherPublicationMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class),
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

        $otherPublicationMainDocumentRequestMapper = Mockery::mock(OtherPublicationMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($otherPublication, $mainDocumentRequestDto, null, $otherPublicationMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($otherPublication, $otherPublicationRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($otherPublication, null, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $dossierPersister,
            $otherPublicationRequestMapper,
            $dossierAttachmentSynchronizer,
            $otherPublicationMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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

        $otherPublicationMainDocumentRequestMapper = Mockery::mock(OtherPublicationMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($otherPublication, null, $noticeNotPublicRequestDto, $otherPublicationMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($otherPublication, null, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $dossierPersister,
            $otherPublicationRequestMapper,
            $dossierAttachmentSynchronizer,
            $otherPublicationMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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
        $mainDocumentSnapshot = MainDocumentSnapshot::of($otherPublication);
        $attachmentEvents = [];

        $otherPublicationRequestMapper = Mockery::mock(OtherPublicationRequestMapper::class);
        $otherPublicationRequestMapper->expects('update')->with($otherPublication, $otherPublicationRequestDto, $organisation, $department, $subject);

        $otherPublicationMainDocumentRequestMapper = Mockery::mock(OtherPublicationMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($otherPublication, $mainDocumentRequestDto, null, $otherPublicationMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($otherPublication, $otherPublicationRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($otherPublication)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($otherPublication, $mainDocumentSnapshot, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $dossierPersister,
            $otherPublicationRequestMapper,
            $dossierAttachmentSynchronizer,
            $otherPublicationMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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
        $mainDocumentSnapshot = MainDocumentSnapshot::of($otherPublication);
        $attachmentEvents = [];

        $otherPublicationRequestMapper = Mockery::mock(OtherPublicationRequestMapper::class);
        $otherPublicationRequestMapper->expects('update')->with($otherPublication, $otherPublicationRequestDto, $organisation, $department, null);

        $otherPublicationMainDocumentRequestMapper = Mockery::mock(OtherPublicationMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($otherPublication, null, $noticeNotPublicRequestDto, $otherPublicationMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($otherPublication)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($otherPublication, $mainDocumentSnapshot, $attachmentEvents);

        $otherPublicationStrategy = new OtherPublicationStrategy(
            $dossierPersister,
            $otherPublicationRequestMapper,
            $dossierAttachmentSynchronizer,
            $otherPublicationMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $otherPublicationStrategy->update($otherPublication, $otherPublicationRequestDto, $organisation, $department, null);
    }
}
