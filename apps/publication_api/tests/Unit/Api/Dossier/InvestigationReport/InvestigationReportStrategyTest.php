<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportRequestMapper;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportStrategy;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
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
            Mockery::mock(DossierPersister::class),
            Mockery::mock(InvestigationReportRequestMapper::class),
            Mockery::mock(DossierAttachmentSynchronizer::class),
            Mockery::mock(InvestigationReportMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class),
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

        $investigationReportMainDocumentRequestMapper = Mockery::mock(InvestigationReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($investigationReport, $mainDocumentRequestDto, null, $investigationReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($investigationReport, $investigationReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($investigationReport, null, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $dossierPersister,
            $investigationReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $investigationReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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

        $investigationReportMainDocumentRequestMapper = Mockery::mock(InvestigationReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($investigationReport, null, $noticeNotPublicRequestDto, $investigationReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($investigationReport, null, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $dossierPersister,
            $investigationReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $investigationReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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
        $mainDocumentSnapshot = MainDocumentSnapshot::of($investigationReport);
        $attachmentEvents = [];

        $investigationReportRequestMapper = Mockery::mock(InvestigationReportRequestMapper::class);
        $investigationReportRequestMapper->expects('update')
            ->with($investigationReport, $investigationReportRequestDto, $organisation, $department, $subject);

        $investigationReportMainDocumentRequestMapper = Mockery::mock(InvestigationReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($investigationReport, $mainDocumentRequestDto, null, $investigationReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($investigationReport, $investigationReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($investigationReport)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($investigationReport, $mainDocumentSnapshot, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $dossierPersister,
            $investigationReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $investigationReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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
        $mainDocumentSnapshot = MainDocumentSnapshot::of($investigationReport);
        $attachmentEvents = [];

        $investigationReportRequestMapper = Mockery::mock(InvestigationReportRequestMapper::class);
        $investigationReportRequestMapper->expects('update')
            ->with($investigationReport, $investigationReportRequestDto, $organisation, $department, null);

        $investigationReportMainDocumentRequestMapper = Mockery::mock(InvestigationReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($investigationReport, null, $noticeNotPublicRequestDto, $investigationReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($investigationReport)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($investigationReport, $mainDocumentSnapshot, $attachmentEvents);

        $investigationReportStrategy = new InvestigationReportStrategy(
            $dossierPersister,
            $investigationReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $investigationReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $investigationReportStrategy->update($investigationReport, $investigationReportRequestDto, $organisation, $department, null);
    }
}
