<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\InvestigationReport;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\DossierResponseMapper;
use PublicationApi\Api\Dossier\InvestigationReport\Uploads\Attachment\InvestigationReportUploadAttachmentResource;
use PublicationApi\Api\Dossier\InvestigationReport\Uploads\MainDocument\InvestigationReportUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDto;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<InvestigationReport,InvestigationReportResponseDto>
 */
readonly class InvestigationReportResponseMapper implements DossierResponseMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private NoticeNotPublicResponseDtoFactory $noticeNotPublicResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,InvestigationReport> $dossiers
     *
     * @return list<InvestigationReportResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): InvestigationReportResponseDto
    {
        Assert::isInstanceOf($dossier, InvestigationReport::class);

        return new InvestigationReportResponseDto(
            $dossier->getId(),
            $dossier->getExternalId(),
            OrganisationMapper::fromEntity($dossier->getOrganisation()),
            $dossier->getDossierNumber(),
            $dossier->getTitle(),
            $dossier->getSummary(),
            SubjectMapper::fromNullableEntity($dossier->getSubject()),
            $this->department($dossier),
            $dossier->getPublicationDate(),
            $dossier->getStatus(),
            $this->mainDocument($dossier),
            $this->noticeNotPublic($dossier),
            $this->attachmentResponseDtoFactory->fromDossier($dossier, InvestigationReportUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            self::dossierDate($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(InvestigationReport $investigationReport): DepartmentResponseDto
    {
        $department = $investigationReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(InvestigationReport $investigationReport): ?InvestigationReportMainDocumentResponseDto
    {
        $mainDocument = $investigationReport->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            InvestigationReportUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            InvestigationReportMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(InvestigationReport $investigationReport): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $investigationReport->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function dossierDate(InvestigationReport $investigationReport): PlainDate
    {
        $dateFrom = $investigationReport->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(InvestigationReport $investigationReport): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(InvestigationReportResource::ROUTE_NAME_GET_INVESTIGATION_REPORT, [
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
