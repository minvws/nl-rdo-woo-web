<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportMainDocumentRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportRequestDto;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class InvestigationReportRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $investigationReportRequestDto = new InvestigationReportRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            new InvestigationReportMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
            ),
        );

        $investigationReportRequestMapper = new InvestigationReportRequestMapper();

        $investigationReport = $investigationReportRequestMapper->create(
            $investigationReportRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $investigationReport->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $investigationReport->getStatus());
        self::assertSame($documentPrefix, $investigationReport->getDocumentPrefix());
        self::assertSame($dossierDate, $investigationReport->getDateFrom());
        self::assertSame($publicationDate, $investigationReport->getPublicationDate());
        self::assertSame($dossierNumber, $investigationReport->getDossierNumber());
        self::assertSame($summary, $investigationReport->getSummary());
        self::assertSame($title, $investigationReport->getTitle());
        self::assertSame($organisation, $investigationReport->getOrganisation());
        self::assertSame($subject, $investigationReport->getSubject());
        self::assertSame([$department], $investigationReport->getDepartments()->getValues());
    }
}
