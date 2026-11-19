<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportRequestMapper;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportStrategy;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class AnnualReportStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $annualReportStrategy = new AnnualReportStrategy(
            Mockery::mock(DossierPersister::class),
            Mockery::mock(AnnualReportRequestMapper::class),
            Mockery::mock(DossierAttachmentSynchronizer::class),
            Mockery::mock(AnnualReportMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class),
        );

        self::assertSame(AnnualReport::class, $annualReportStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            AttachmentType::ANNUAL_REPORT,
        );
        $annualReportRequestDto = new AnnualReportRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            (int) $this->getFaker()->year(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
        );

        $annualReport = new AnnualReport();
        $attachmentEvents = [];

        $annualReportRequestMapper = Mockery::mock(AnnualReportRequestMapper::class);
        $annualReportRequestMapper->expects('create')->with(
            $annualReportRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($annualReport);

        $annualReportMainDocumentRequestMapper = Mockery::mock(AnnualReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($annualReport, $mainDocumentRequestDto, null, $annualReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($annualReport, $annualReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($annualReport, null, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $dossierPersister,
            $annualReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $annualReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        self::assertSame($annualReport, $annualReportStrategy->create(
            $annualReportRequestDto,
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
        $annualReportRequestDto = new AnnualReportRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            (int) $this->getFaker()->year(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $annualReport = new AnnualReport();
        $attachmentEvents = [];

        $annualReportRequestMapper = Mockery::mock(AnnualReportRequestMapper::class);
        $annualReportRequestMapper->expects('create')->andReturn($annualReport);

        $annualReportMainDocumentRequestMapper = Mockery::mock(AnnualReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($annualReport, null, $noticeNotPublicRequestDto, $annualReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($annualReport, null, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $dossierPersister,
            $annualReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $annualReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        self::assertSame($annualReport, $annualReportStrategy->create(
            $annualReportRequestDto,
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

        $mainDocumentRequestDto = new AnnualReportMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            AttachmentType::ANNUAL_REPORT,
        );
        $annualReportRequestDto = new AnnualReportRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            (int) $this->getFaker()->year(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
        );

        $annualReport = new AnnualReport();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($annualReport);
        $attachmentEvents = [];

        $annualReportRequestMapper = Mockery::mock(AnnualReportRequestMapper::class);
        $annualReportRequestMapper->expects('update')->with($annualReport, $annualReportRequestDto, $organisation, $department, $subject);

        $annualReportMainDocumentRequestMapper = Mockery::mock(AnnualReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($annualReport, $mainDocumentRequestDto, null, $annualReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($annualReport, $annualReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($annualReport)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($annualReport, $mainDocumentSnapshot, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $dossierPersister,
            $annualReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $annualReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $annualReportStrategy->update($annualReport, $annualReportRequestDto, $organisation, $department, $subject);
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
        $annualReportRequestDto = new AnnualReportRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            (int) $this->getFaker()->year(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $annualReport = new AnnualReport();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($annualReport);
        $attachmentEvents = [];

        $annualReportRequestMapper = Mockery::mock(AnnualReportRequestMapper::class);
        $annualReportRequestMapper->expects('update')->with($annualReport, $annualReportRequestDto, $organisation, $department, null);

        $annualReportMainDocumentRequestMapper = Mockery::mock(AnnualReportMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($annualReport, null, $noticeNotPublicRequestDto, $annualReportMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($annualReport)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($annualReport, $mainDocumentSnapshot, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $dossierPersister,
            $annualReportRequestMapper,
            $dossierAttachmentSynchronizer,
            $annualReportMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $annualReportStrategy->update($annualReport, $annualReportRequestDto, $organisation, $department, null);
    }
}
