<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDto;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDto;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Subject\Subject;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

class InvestigationReportMapper
{
    /**
     * @param array<array-key,InvestigationReport> $investigationReports
     *
     * @return list<InvestigationReportDto>
     */
    public static function fromEntities(array $investigationReports): array
    {
        return array_values(array_map(
            self::fromEntity(...),
            $investigationReports,
        ));
    }

    public static function fromEntity(InvestigationReport $investigationReport): InvestigationReportDto
    {
        $mainDocument = $investigationReport->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = MainDocumentResponseDto::fromEntity($mainDocument);

        $dateFrom = $investigationReport->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $investigationReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new InvestigationReportDto(
            $investigationReport->getId(),
            $investigationReport->getExternalId(),
            OrganisationReferenceDto::fromEntity($investigationReport->getOrganisation()),
            $investigationReport->getDocumentPrefix(),
            $investigationReport->getDossierNr(),
            $investigationReport->getInternalReference(),
            $investigationReport->getTitle(),
            $investigationReport->getSummary(),
            $investigationReport->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $investigationReport->getPublicationDate(),
            $investigationReport->getStatus(),
            $mainDocumentDto,
            AttachmentResponseDto::fromEntities($investigationReport->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        InvestigationReportRequestDto $investigationReportRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        string $externalId,
    ): InvestigationReport {
        $investigationReport = new InvestigationReport();
        $investigationReport->setExternalId($externalId);
        $investigationReport->setStatus(DossierStatus::NEW);

        self::update($investigationReport, $investigationReportRequestDto, $organisation, $department, $subject);

        return $investigationReport;
    }

    public static function update(
        InvestigationReport $investigationReport,
        InvestigationReportRequestDto $investigationReportRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): InvestigationReport {
        $investigationReport->setDateFrom($investigationReportRequestDto->dossierDate);
        $investigationReport->setDepartments([$department]);
        $investigationReport->setDocumentPrefix($investigationReportRequestDto->prefix);
        $investigationReport->setDossierNr($investigationReportRequestDto->dossierNumber);
        $investigationReport->setInternalReference($investigationReportRequestDto->internalReference);
        $investigationReport->setOrganisation($organisation);
        $investigationReport->setPublicationDate($investigationReportRequestDto->publicationDate);
        $investigationReport->setSubject($subject);
        $investigationReport->setSummary($investigationReportRequestDto->summary);
        $investigationReport->setTitle($investigationReportRequestDto->title);

        return $investigationReport;
    }
}
