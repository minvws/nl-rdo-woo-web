<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\AnnualReport;

use Carbon\CarbonImmutable;
use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class AnnualReportMapper
{
    /**
     * @param array<array-key,AnnualReport> $annualReports
     *
     * @return list<AnnualReportDto>
     */
    public static function fromEntities(array $annualReports): array
    {
        return array_values(array_map(self::fromEntity(...), $annualReports));
    }

    public static function fromEntity(AnnualReport $annualReport): AnnualReportDto
    {
        $mainDocument = $annualReport->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $annualReport->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $annualReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new AnnualReportDto(
            $annualReport->getId(),
            $annualReport->getExternalId(),
            OrganisationReferenceDto::fromEntity($annualReport->getOrganisation()),
            $annualReport->getDocumentPrefix(),
            $annualReport->getDossierNr(),
            $annualReport->getInternalReference(),
            $annualReport->getTitle(),
            $annualReport->getSummary(),
            $annualReport->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $annualReport->getPublicationDate(),
            $annualReport->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($annualReport->getAttachments()->toArray()),
            (int) $dateFrom->format('Y'),
        );
    }

    public static function create(
        AnnualReportRequestDto $annualReportRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): AnnualReport {
        $annualReport = new AnnualReport();
        $annualReport->setExternalId($externalId);
        $annualReport->setStatus(DossierStatus::NEW);

        self::update($annualReport, $annualReportRequestDto, $organisation, $department, $subject);

        return $annualReport;
    }

    public static function update(
        AnnualReport $annualReport,
        AnnualReportRequestDto $annualReportRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): AnnualReport {
        $dossierDate = CarbonImmutable::createFromFormat('Y', (string) $annualReportRequestDto->year);
        Assert::isInstanceOf($dossierDate, CarbonImmutable::class);
        $dossierDate->startOfYear();

        $annualReport->setDateFrom($dossierDate);
        $annualReport->setDepartments([$department]);
        $annualReport->setDocumentPrefix($annualReportRequestDto->prefix);
        $annualReport->setDossierNr($annualReportRequestDto->dossierNumber);
        $annualReport->setInternalReference($annualReportRequestDto->internalReference);
        $annualReport->setOrganisation($organisation);
        $annualReport->setPublicationDate($annualReportRequestDto->publicationDate);
        $annualReport->setSubject($subject);
        $annualReport->setSummary($annualReportRequestDto->summary);
        $annualReport->setTitle($annualReportRequestDto->title);

        return $annualReport;
    }
}
