<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentResponseDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class InvestigationReportResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes());

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $investigationReport = new InvestigationReport();
        $investigationReport->setOrganisation($organisation);
        $investigationReport->addDepartment($department);
        $investigationReport->setStatus(DossierStatus::CONCEPT);
        $investigationReport->setMainDocument(
            new InvestigationReportMainDocument(
                $investigationReport,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $investigationReport->setExternalId($externalId);
        $investigationReport->setDossierNumber($dossierNumber);
        $investigationReport->setTitle($title);
        $investigationReport->setSummary($summary);
        $investigationReport->setDateFrom($dossierDate);
        $investigationReport->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new InvestigationReportMainDocumentResponseDto(
            Uuid::v6(),
            $mainDocumentType,
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->plainDate(),
            [],
            $this->getFaker()->fileName()->toString(),
            UploadStatus::UPLOAD_REQUIRED,
            new LinkCollection(),
        );
        $attachmentResponseDtos = [];

        $mainDocumentResponseDtoFactory = Mockery::mock(MainDocumentResponseDtoFactory::class);
        $mainDocumentResponseDtoFactory->expects('fromEntity')->andReturn($mainDocumentResponseDto);

        $attachmentResponseDtoFactory = Mockery::mock(AttachmentResponseDtoFactory::class);
        $attachmentResponseDtoFactory->expects('fromDossier')->andReturn($attachmentResponseDtos);

        $apiUrlGenerator = Mockery::mock(ApiUrlGenerator::class);
        $apiUrlGenerator->expects('buildUrlFromRoute')->andReturn(Url::create($this->getFaker()->url()));

        $investigationReportResponseMapper = new InvestigationReportResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $investigationReportResponseDto = $investigationReportResponseMapper->fromEntity($investigationReport);

        self::assertSame($investigationReport->getId(), $investigationReportResponseDto->id);
        self::assertSame($externalId, $investigationReportResponseDto->externalId);
        self::assertSame($dossierNumber, $investigationReportResponseDto->dossierNumber);
        self::assertSame($title, $investigationReportResponseDto->title);
        self::assertSame($summary, $investigationReportResponseDto->summary);
        self::assertSame($publicationDate, $investigationReportResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $investigationReportResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $investigationReportResponseDto->mainDocument);
        self::assertNull($investigationReportResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $investigationReportResponseDto->attachments);
        self::assertSame($dossierDate, $investigationReportResponseDto->dossierDate);
    }
}
