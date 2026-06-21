<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\AnnualReport;

use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment\AnnualReportUploadAttachmentResource;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\MainDocument\AnnualReportUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;
use function sprintf;

readonly class AnnualReportMapper
{
    public function __construct(
        private AttachmentResponseDtoFactory $attachmentResponseDtoFactory,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
    ) {
    }

    /**
     * @param array<array-key,AnnualReport> $annualReports
     *
     * @return list<AnnualReportResponseDto>
     */
    public function fromEntities(array $annualReports): array
    {
        return array_values(array_map($this->fromEntity(...), $annualReports));
    }

    public function fromEntity(AnnualReport $annualReport): AnnualReportResponseDto
    {
        $mainDocument = $annualReport->getMainDocument();
        Assert::notNull($mainDocument);

        $dateFrom = $annualReport->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $annualReport->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new AnnualReportResponseDto(
            $annualReport->getId(),
            $annualReport->getExternalId(),
            OrganisationMapper::fromEntity($annualReport->getOrganisation()),
            $annualReport->getDossierNr(),
            $annualReport->getTitle(),
            $annualReport->getSummary(),
            SubjectMapper::fromNullableEntity($annualReport->getSubject()),
            DepartmentMapper::fromEntity($department),
            $annualReport->getPublicationDate(),
            $annualReport->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                AnnualReportUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                AnnualReportMainDocumentResponseDto::class,
            ),
            $this->attachmentResponseDtoFactory->fromDossier($annualReport, AnnualReportUploadAttachmentResource::ROUTE_NAME_UPLOAD),
            (int) $dateFrom->format('Y'),
            $this->getHalLinks($annualReport),
        );
    }

    public static function create(
        AnnualReportRequestDto $annualReportRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): AnnualReport {
        $annualReport = new AnnualReport();
        $annualReport->setExternalId($externalId);
        $annualReport->setStatus(DossierStatus::NEW);
        $annualReport->setDocumentPrefix($documentPrefix);

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
        $annualReport->setDateFrom(PlainDate::createFromFormat('Y-m-d', sprintf('%d-01-01', $annualReportRequestDto->year)));
        $annualReport->setDepartments([$department]);
        $annualReport->setDossierNr($annualReportRequestDto->dossierNumber);
        $annualReport->setOrganisation($organisation);
        $annualReport->setPublicationDate($annualReportRequestDto->publicationDate);
        $annualReport->setSubject($subject);
        $annualReport->setSummary($annualReportRequestDto->summary);
        $annualReport->setTitle($annualReportRequestDto->title);

        return $annualReport;
    }

    private function getHalLinks(AnnualReport $annualReport): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->publicUrlGenerator->buildUrlFromRoute(AnnualReportResource::ROUTE_NAME_GET_ANNUAL_REPORT, [
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
