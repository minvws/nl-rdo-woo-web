<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use Mockery;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\Disposition\DispositionRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionRequestMapper;
use PublicationApi\Api\Dossier\Disposition\DispositionStrategy;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class DispositionStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $dispositionStrategy = new DispositionStrategy(
            Mockery::mock(DossierPersister::class),
            Mockery::mock(DispositionRequestMapper::class),
            Mockery::mock(DossierAttachmentSynchronizer::class),
            Mockery::mock(DispositionMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class),
        );

        self::assertSame(Disposition::class, $dispositionStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );
        $dispositionRequestDto = new DispositionRequestDto(
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

        $disposition = new Disposition();
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('create')->with(
            $dispositionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($disposition);

        $dispositionMainDocumentRequestMapper = Mockery::mock(DispositionMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($disposition, $mainDocumentRequestDto, null, $dispositionMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($disposition, $dispositionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($disposition, null, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dossierPersister,
            $dispositionRequestMapper,
            $dossierAttachmentSynchronizer,
            $dispositionMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        self::assertSame($disposition, $dispositionStrategy->create(
            $dispositionRequestDto,
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
        $dispositionRequestDto = new DispositionRequestDto(
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

        $disposition = new Disposition();
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('create')->andReturn($disposition);

        $dispositionMainDocumentRequestMapper = Mockery::mock(DispositionMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($disposition, null, $noticeNotPublicRequestDto, $dispositionMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($disposition, null, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dossierPersister,
            $dispositionRequestMapper,
            $dossierAttachmentSynchronizer,
            $dispositionMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        self::assertSame($disposition, $dispositionStrategy->create(
            $dispositionRequestDto,
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

        $mainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );
        $dispositionRequestDto = new DispositionRequestDto(
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

        $disposition = new Disposition();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($disposition);
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('update')->with($disposition, $dispositionRequestDto, $organisation, $department, $subject);

        $dispositionMainDocumentRequestMapper = Mockery::mock(DispositionMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($disposition, $mainDocumentRequestDto, null, $dispositionMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($disposition, $dispositionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($disposition)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($disposition, $mainDocumentSnapshot, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dossierPersister,
            $dispositionRequestMapper,
            $dossierAttachmentSynchronizer,
            $dispositionMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $dispositionStrategy->update($disposition, $dispositionRequestDto, $organisation, $department, $subject);
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
        $dispositionRequestDto = new DispositionRequestDto(
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

        $disposition = new Disposition();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($disposition);
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('update')->with($disposition, $dispositionRequestDto, $organisation, $department, null);

        $dispositionMainDocumentRequestMapper = Mockery::mock(DispositionMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($disposition, null, $noticeNotPublicRequestDto, $dispositionMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($disposition)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($disposition, $mainDocumentSnapshot, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dossierPersister,
            $dispositionRequestMapper,
            $dossierAttachmentSynchronizer,
            $dispositionMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $dispositionStrategy->update($disposition, $dispositionRequestDto, $organisation, $department, null);
    }
}
