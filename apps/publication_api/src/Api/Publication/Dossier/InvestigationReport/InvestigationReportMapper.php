<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\InvestigationReport;

use PublicationApi\Api\Publication\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Publication\Department\DepartmentReferenceDto;
use PublicationApi\Api\Publication\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Publication\Organisation\OrganisationReferenceDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class InvestigationReportMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,InvestigationReport> $investigationReports
     *
     * @return list<InvestigationReportResponseDto>
     */
    public function fromEntities(array $investigationReports): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $investigationReports,
        ));
    }

    public function fromEntity(InvestigationReport $investigationReport): InvestigationReportResponseDto
    {
        $mainDocument = $investigationReport->getMainDocument();
        Assert::notNull($mainDocument);

        $mainDocumentDto = $this->mainDocumentResponseDtoFactory->fromEntity($mainDocument);

        $dateFrom = $investigationReport->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $investigationReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new InvestigationReportResponseDto(
            $investigationReport->getId(),
            $investigationReport->getExternalId(),
            OrganisationReferenceDto::fromEntity($investigationReport->getOrganisation()),
            $investigationReport->getDossierNr(),
            $investigationReport->getTitle(),
            $investigationReport->getSummary(),
            $investigationReport->getSubject()?->getName(),
            DepartmentReferenceDto::fromEntity($department),
            $investigationReport->getPublicationDate(),
            $investigationReport->getStatus(),
            $mainDocumentDto,
            $this->attachmentResponseDtoFactory->fromEntities($investigationReport->getAttachments()->toArray()),
            $dateFrom,
        );
    }

    public static function create(
        InvestigationReportRequestDto $investigationReportRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): InvestigationReport {
        $investigationReport = new InvestigationReport();
        $investigationReport->setExternalId($externalId);
        $investigationReport->setStatus(DossierStatus::NEW);
        $investigationReport->setDocumentPrefix($documentPrefix);

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
        $investigationReport->setDossierNr($investigationReportRequestDto->dossierNumber);
        $investigationReport->setOrganisation($organisation);
        $investigationReport->setPublicationDate($investigationReportRequestDto->publicationDate);
        $investigationReport->setSubject($subject);
        $investigationReport->setSummary($investigationReportRequestDto->summary);
        $investigationReport->setTitle($investigationReportRequestDto->title);

        return $investigationReport;
    }
}
