<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Department\DepartmentResponseDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\Uploads\MainDocument\ComplaintJudgementUploadMainDocumentResource;
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
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

/**
 * @implements DossierResponseMapper<ComplaintJudgement,ComplaintJudgementResponseDto>
 */
readonly class ComplaintJudgementResponseMapper implements DossierResponseMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private NoticeNotPublicResponseDtoFactory $noticeNotPublicResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,ComplaintJudgement> $dossiers
     *
     * @return list<ComplaintJudgementResponseDto>
     */
    public function fromEntities(array $dossiers): array
    {
        return array_values(array_map($this->fromEntity(...), $dossiers));
    }

    public function fromEntity(AbstractDossier $dossier): ComplaintJudgementResponseDto
    {
        Assert::isInstanceOf($dossier, ComplaintJudgement::class);

        return new ComplaintJudgementResponseDto(
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
            self::dossierDate($dossier),
            $this->getHalLinks($dossier),
        );
    }

    private function department(ComplaintJudgement $complaintJudgement): DepartmentResponseDto
    {
        $department = $complaintJudgement->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return DepartmentMapper::fromEntity($department);
    }

    private function mainDocument(ComplaintJudgement $complaintJudgement): ?ComplaintJudgementMainDocumentResponseDto
    {
        $mainDocument = $complaintJudgement->getMainDocument();
        if ($mainDocument === null) {
            return null;
        }

        return $this->mainDocumentResponseDtoFactory->fromEntity(
            $mainDocument,
            ComplaintJudgementUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
            ComplaintJudgementMainDocumentResponseDto::class,
        );
    }

    private function noticeNotPublic(ComplaintJudgement $complaintJudgement): ?NoticeNotPublicResponseDto
    {
        $noticeNotPublic = $complaintJudgement->getNoticeNotPublic();
        if ($noticeNotPublic === null) {
            return null;
        }

        return $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic);
    }

    private static function dossierDate(ComplaintJudgement $complaintJudgement): PlainDate
    {
        $dateFrom = $complaintJudgement->getDateFrom();
        Assert::notNull($dateFrom);

        return $dateFrom;
    }

    private function getHalLinks(ComplaintJudgement $complaintJudgement): LinkCollection
    {
        $linkCollection = new LinkCollection();
        $linkCollection->set(
            LinkCollection::SELF,
            new Link($this->apiUrlGenerator->buildUrlFromRoute(ComplaintJudgementResource::ROUTE_NAME_GET_COMPLAINT_JUDGEMENT, [
                'organisationId' => $complaintJudgement->getOrganisation()->getId(),
                'dossierExternalId' => $complaintJudgement->getExternalId(),
            ])),
        );

        if ($complaintJudgement->getStatus()->isPublished()) {
            $linkCollection->set(
                LinkCollection::PUBLIC,
                new Link(Url::create($this->dossierPathHelper->getAbsoluteDetailsPath($complaintJudgement))),
            );
        }

        return $linkCollection;
    }
}
