<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportAttachmentSynchronizer;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportPersister;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportRequestMapper;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportSnapshot;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
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
            Mockery::mock(AnnualReportPersister::class),
            Mockery::mock(AnnualReportRequestMapper::class),
            Mockery::mock(AnnualReportAttachmentSynchronizer::class),
            Mockery::mock(AnnualReportMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
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

        $annualReportMainDocumentSynchronizer = Mockery::mock(AnnualReportMainDocumentSynchronizer::class);
        $annualReportMainDocumentSynchronizer->expects('create')->with($annualReport, $mainDocumentRequestDto);

        $annualReportAttachmentSynchronizer = Mockery::mock(AnnualReportAttachmentSynchronizer::class);
        $annualReportAttachmentSynchronizer->expects('create')
            ->with($annualReport, $annualReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $annualReportPersister = Mockery::mock(AnnualReportPersister::class);
        $annualReportPersister->expects('persist')->with($annualReport, null, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $annualReportPersister,
            $annualReportRequestMapper,
            $annualReportAttachmentSynchronizer,
            $annualReportMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
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

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($annualReport, $noticeNotPublicRequestDto);

        $annualReportAttachmentSynchronizer = Mockery::mock(AnnualReportAttachmentSynchronizer::class);
        $annualReportAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $annualReportPersister = Mockery::mock(AnnualReportPersister::class);
        $annualReportPersister->expects('persist')->with($annualReport, null, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $annualReportPersister,
            $annualReportRequestMapper,
            $annualReportAttachmentSynchronizer,
            Mockery::mock(AnnualReportMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
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
        $annualReportSnapshot = AnnualReportSnapshot::of($annualReport);
        $attachmentEvents = [];

        $annualReportRequestMapper = Mockery::mock(AnnualReportRequestMapper::class);
        $annualReportRequestMapper->expects('update')->with($annualReport, $annualReportRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($annualReport);

        $annualReportMainDocumentSynchronizer = Mockery::mock(AnnualReportMainDocumentSynchronizer::class);
        $annualReportMainDocumentSynchronizer->expects('update')->with($annualReport, $mainDocumentRequestDto);

        $annualReportAttachmentSynchronizer = Mockery::mock(AnnualReportAttachmentSynchronizer::class);
        $annualReportAttachmentSynchronizer->expects('update')
            ->with($annualReport, $annualReportRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $annualReportPersister = Mockery::mock(AnnualReportPersister::class);
        $annualReportPersister->expects('snapshot')->with($annualReport)->andReturn($annualReportSnapshot);
        $annualReportPersister->expects('persist')->with($annualReport, $annualReportSnapshot, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $annualReportPersister,
            $annualReportRequestMapper,
            $annualReportAttachmentSynchronizer,
            $annualReportMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
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
        $annualReportSnapshot = AnnualReportSnapshot::of($annualReport);
        $attachmentEvents = [];

        $annualReportRequestMapper = Mockery::mock(AnnualReportRequestMapper::class);
        $annualReportRequestMapper->expects('update')->with($annualReport, $annualReportRequestDto, $organisation, $department, null);

        $annualReportMainDocumentSynchronizer = Mockery::mock(AnnualReportMainDocumentSynchronizer::class);
        $annualReportMainDocumentSynchronizer->expects('delete')->with($annualReport);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($annualReport, $noticeNotPublicRequestDto);

        $annualReportAttachmentSynchronizer = Mockery::mock(AnnualReportAttachmentSynchronizer::class);
        $annualReportAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $annualReportPersister = Mockery::mock(AnnualReportPersister::class);
        $annualReportPersister->expects('snapshot')->with($annualReport)->andReturn($annualReportSnapshot);
        $annualReportPersister->expects('persist')->with($annualReport, $annualReportSnapshot, $attachmentEvents);

        $annualReportStrategy = new AnnualReportStrategy(
            $annualReportPersister,
            $annualReportRequestMapper,
            $annualReportAttachmentSynchronizer,
            $annualReportMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $annualReportStrategy->update($annualReport, $annualReportRequestDto, $organisation, $department, null);
    }
}
