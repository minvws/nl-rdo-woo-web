<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\InvestigationReport\Uploads\Attachment\InvestigationReportUploadAttachmentResource;
use PublicationApi\Api\Dossier\InvestigationReport\Uploads\MainDocument\InvestigationReportUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class InvestigationReportMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
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

        $dateFrom = $investigationReport->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $investigationReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new InvestigationReportResponseDto(
            $investigationReport->getId(),
            $investigationReport->getExternalId(),
            OrganisationMapper::fromEntity($investigationReport->getOrganisation()),
            $investigationReport->getDossierNr(),
            $investigationReport->getTitle(),
            $investigationReport->getSummary(),
            SubjectMapper::fromNullableEntity($investigationReport->getSubject()),
            DepartmentMapper::fromEntity($department),
            $investigationReport->getPublicationDate(),
            $investigationReport->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                InvestigationReportUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                InvestigationReportMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($investigationReport, InvestigationReportUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            $dateFrom,
            $this->getHalLinks($investigationReport),
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

    private function getHalLinks(InvestigationReport $investigationReport): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(InvestigationReportResource::ROUTE_NAME_GET_INVESTIGATION_REPORT, [
                'organisationId' => $investigationReport->getOrganisation()->getId(),
                'dossierExternalId' => $investigationReport->getExternalId(),
            ])),
        );

        if ($investigationReport->getStatus()->isPublished()) {
            $linkCollection->set(
                LinkCollection::PUBLIC,
                new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($investigationReport))),
            );
        }

        return $linkCollection;
    }
}
