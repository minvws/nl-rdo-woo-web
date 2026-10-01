<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment\AnnualReportUploadAttachmentResource;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\MainDocument\AnnualReportUploadMainDocumentResource;
use PublicationApi\Api\Dossier\DossierResponseMapper;
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
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<AnnualReport,AnnualReportResponseDto>
 */
readonly class AnnualReportResponseMapper implements DossierResponseMapper
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
     * @param array<array-key,AnnualReport> $dossiers
     *
     * @return list<AnnualReportResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): AnnualReportResponseDto
    {
        Assert::isInstanceOf($dossier, AnnualReport::class);

        return new AnnualReportResponseDto(
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
            $this->attachmentResponseDtoFactory->fromDossier($dossier, AnnualReportUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            self::year($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(AnnualReport $annualReport): DepartmentResponseDto
    {
        $department = $annualReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(AnnualReport $annualReport): ?AnnualReportMainDocumentResponseDto
    {
        $mainDocument = $annualReport->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            AnnualReportUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            AnnualReportMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(AnnualReport $annualReport): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $annualReport->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function year(AnnualReport $annualReport): int
    {
        $dateFrom = $annualReport->getDateFrom();
        Assert::notNull($dateFrom);

        return (int) $dateFrom->format('Y');
    }

    private function getHalLinks(AnnualReport $annualReport): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(AnnualReportResource::ROUTE_NAME_GET_ANNUAL_REPORT, [
                'organisationId' => $annualReport->getOrganisation()->getId(),
                'dossierExternalId' => $annualReport->getExternalId(),
            ])),
        );

        if ($annualReport->getStatus()->isPublished()) {
            $linkCollection->set(LinkCollection::PUBLIC, new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($annualReport))));
        }

        return $linkCollection;
    }
}
