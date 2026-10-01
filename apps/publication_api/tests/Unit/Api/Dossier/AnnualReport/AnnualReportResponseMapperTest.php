<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentResponseDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

use function sprintf;

final class AnnualReportResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $publicationDate = $this->getFaker()->plainDate();
        $year = (int) $this->getFaker()->year();

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $annualReport = new AnnualReport();
        $annualReport->setOrganisation($organisation);
        $annualReport->addDepartment($department);
        $annualReport->setStatus(DossierStatus::CONCEPT);
        $annualReport->setMainDocument(
            new AnnualReportMainDocument(
                $annualReport,
                $this->getFaker()->plainDate(),
                AttachmentType::ANNUAL_REPORT,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $annualReport->setExternalId($externalId);
        $annualReport->setDossierNumber($dossierNumber);
        $annualReport->setTitle($title);
        $annualReport->setSummary($summary);
        $annualReport->setDateFrom(PlainDate::createFromFormat('Y-m-d', sprintf('%d-01-01', $year)));
        $annualReport->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new AnnualReportMainDocumentResponseDto(
            Uuid::v6(),
            AttachmentType::ANNUAL_REPORT,
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

        $annualReportResponseMapper = new AnnualReportResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $annualReportResponseDto = $annualReportResponseMapper->fromEntity($annualReport);

        self::assertSame($annualReport->getId(), $annualReportResponseDto->id);
        self::assertSame($externalId, $annualReportResponseDto->externalId);
        self::assertSame($dossierNumber, $annualReportResponseDto->dossierNumber);
        self::assertSame($title, $annualReportResponseDto->title);
        self::assertSame($summary, $annualReportResponseDto->summary);
        self::assertSame($publicationDate, $annualReportResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $annualReportResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $annualReportResponseDto->mainDocument);
        self::assertNull($annualReportResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $annualReportResponseDto->attachments);
        self::assertSame($year, $annualReportResponseDto->year);
    }
}
