<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class AnnualReportRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $publicationDate = $this->getFaker()->plainDate();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $year = (int) $this->getFaker()->year();

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $annualReportRequestDto = new AnnualReportRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $year,
            $dossierNumber,
            $publicationDate,
            new AnnualReportMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                AttachmentType::ANNUAL_REPORT,
            ),
        );

        $annualReportRequestMapper = new AnnualReportRequestMapper();

        $annualReport = $annualReportRequestMapper->create(
            $annualReportRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $annualReport->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $annualReport->getStatus());
        self::assertSame($documentPrefix, $annualReport->getDocumentPrefix());
        self::assertSame($year, (int) $annualReport->getDateFrom()?->format('Y'));
        self::assertSame($publicationDate, $annualReport->getPublicationDate());
        self::assertSame($dossierNumber, $annualReport->getDossierNumber());
        self::assertSame($summary, $annualReport->getSummary());
        self::assertSame($title, $annualReport->getTitle());
        self::assertSame($organisation, $annualReport->getOrganisation());
        self::assertSame($subject, $annualReport->getSubject());
        self::assertSame([$department], $annualReport->getDepartments()->getValues());
    }
}
