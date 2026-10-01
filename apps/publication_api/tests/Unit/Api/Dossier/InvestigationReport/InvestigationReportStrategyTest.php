<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportAttachmentSynchronizer;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportPersister;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportRequestMapper;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportSnapshot;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class InvestigationReportStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $investigationReportStrategy = new InvestigationReportStrategy(
            Mockery::mock(InvestigationReportPersister::class),
            Mockery::mock(InvestigationReportRequestMapper::class),
            Mockery::mock(InvestigationReportAttachmentSynchronizer::class),
            Mockery::mock(InvestigationReportMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame(InvestigationReport::class, $investigationReportStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
        );
        $investigationReportRequestDto = new InvestigationReportRequestDto(
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

        $investigationReport = new InvestigationReport();
        $attachmentEvents = [];

        $investigationReportRequestMapper = Mockery::mock(InvestigationReportRequestMapper::class);
        $investigationReportRequestMapper->expects('create')->with(
            $investigationReportRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($investigationReport);

        $investigationReportMainDocumentSynchronizer = Mockery::mock(InvestigationReportMainDocumentSynchronizer::class);
        $investigationReportMainDocumentSynchronizer->expects('create')->with($investigationReport, $mainDocumentRequestDto);

        $investigationReportAttachmentSynchronizer = Mockery::mock(InvestigationReportAttachmentSynchronizer::class);
        $investigationReportAttachmentSynchronizer->expects('create')
            ->with($investigationReport, $investigationReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $investigationReportPersister = Mockery::mock(InvestigationReportPersister::class);
        $investigationReportPersister->expects('persist')->with($investigationReport, null, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $investigationReportPersister,
            $investigationReportRequestMapper,
            $investigationReportAttachmentSynchronizer,
            $investigationReportMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame($investigationReport, $investigationReportStrategy->create(
            $investigationReportRequestDto,
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
        $investigationReportRequestDto = new InvestigationReportRequestDto(
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

        $investigationReport = new InvestigationReport();
        $attachmentEvents = [];

        $investigationReportRequestMapper = Mockery::mock(InvestigationReportRequestMapper::class);
        $investigationReportRequestMapper->expects('create')->andReturn($investigationReport);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($investigationReport, $noticeNotPublicRequestDto);

        $investigationReportAttachmentSynchronizer = Mockery::mock(InvestigationReportAttachmentSynchronizer::class);
        $investigationReportAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $investigationReportPersister = Mockery::mock(InvestigationReportPersister::class);
        $investigationReportPersister->expects('persist')->with($investigationReport, null, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $investigationReportPersister,
            $investigationReportRequestMapper,
            $investigationReportAttachmentSynchronizer,
            Mockery::mock(InvestigationReportMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        self::assertSame($investigationReport, $investigationReportStrategy->create(
            $investigationReportRequestDto,
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

        $mainDocumentRequestDto = new InvestigationReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
        );
        $investigationReportRequestDto = new InvestigationReportRequestDto(
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

        $investigationReport = new InvestigationReport();
        $investigationReportSnapshot = InvestigationReportSnapshot::of($investigationReport);
        $attachmentEvents = [];

        $investigationReportRequestMapper = Mockery::mock(InvestigationReportRequestMapper::class);
        $investigationReportRequestMapper->expects('update')
            ->with($investigationReport, $investigationReportRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($investigationReport);

        $investigationReportMainDocumentSynchronizer = Mockery::mock(InvestigationReportMainDocumentSynchronizer::class);
        $investigationReportMainDocumentSynchronizer->expects('update')->with($investigationReport, $mainDocumentRequestDto);

        $investigationReportAttachmentSynchronizer = Mockery::mock(InvestigationReportAttachmentSynchronizer::class);
        $investigationReportAttachmentSynchronizer->expects('update')
            ->with($investigationReport, $investigationReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $investigationReportPersister = Mockery::mock(InvestigationReportPersister::class);
        $investigationReportPersister->expects('snapshot')->with($investigationReport)->andReturn($investigationReportSnapshot);
        $investigationReportPersister->expects('persist')->with($investigationReport, $investigationReportSnapshot, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $investigationReportPersister,
            $investigationReportRequestMapper,
            $investigationReportAttachmentSynchronizer,
            $investigationReportMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $investigationReportStrategy->update($investigationReport, $investigationReportRequestDto, $organisation, $department, $subject);
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
        $investigationReportRequestDto = new InvestigationReportRequestDto(
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

        $investigationReport = new InvestigationReport();
        $investigationReportSnapshot = InvestigationReportSnapshot::of($investigationReport);
        $attachmentEvents = [];

        $investigationReportRequestMapper = Mockery::mock(InvestigationReportRequestMapper::class);
        $investigationReportRequestMapper->expects('update')
            ->with($investigationReport, $investigationReportRequestDto, $organisation, $department, null);

        $investigationReportMainDocumentSynchronizer = Mockery::mock(InvestigationReportMainDocumentSynchronizer::class);
        $investigationReportMainDocumentSynchronizer->expects('delete')->with($investigationReport);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($investigationReport, $noticeNotPublicRequestDto);

        $investigationReportAttachmentSynchronizer = Mockery::mock(InvestigationReportAttachmentSynchronizer::class);
        $investigationReportAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $investigationReportPersister = Mockery::mock(InvestigationReportPersister::class);
        $investigationReportPersister->expects('snapshot')->with($investigationReport)->andReturn($investigationReportSnapshot);
        $investigationReportPersister->expects('persist')->with($investigationReport, $investigationReportSnapshot, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $investigationReportPersister,
            $investigationReportRequestMapper,
            $investigationReportAttachmentSynchronizer,
            $investigationReportMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $investigationReportStrategy->update($investigationReport, $investigationReportRequestDto, $organisation, $department, null);
    }
}
