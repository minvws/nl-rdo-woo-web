<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\ComplaintJudgement\Uploads\MainDocument\ComplaintJudgementUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class ComplaintJudgementMapper
{
    public function __construct(
        private ApiUrlGenerator $apiUrlGenerator,
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private NoticeNotPublicResponseDtoFactory $noticeNotPublicResponseDtoFactory,
    ) {
    }

    /**
     * @param array<array-key,ComplaintJudgement> $complaintJudgements
     *
     * @return list<ComplaintJudgementResponseDto>
     */
    public function fromEntities(array $complaintJudgements): array
    {
        return array_values(array_map(
            $this->fromEntity(...),
            $complaintJudgements,
        ));
    }

    public function fromEntity(ComplaintJudgement $complaintJudgement): ComplaintJudgementResponseDto
    {
        $mainDocument = $complaintJudgement->getMainDocument();
        $noticeNotPublic = $complaintJudgement->getNoticeNotPublic();

        $dateFrom = $complaintJudgement->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $complaintJudgement->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        $mainDocumentDto = $mainDocument !== null
            ? $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                ComplaintJudgementUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                ComplaintJudgementMainDocumentResponseDto::class,
            )
            : null;

        $noticeNotPublicDto = $noticeNotPublic !== null
            ? $this->noticeNotPublicResponseDtoFactory->fromEntity($noticeNotPublic)
            : null;

        return new ComplaintJudgementResponseDto(
            $complaintJudgement->getId(),
            $complaintJudgement->getExternalId(),
            OrganisationMapper::fromEntity($complaintJudgement->getOrganisation()),
            $complaintJudgement->getDossierNumber(),
            $complaintJudgement->getTitle(),
            $complaintJudgement->getSummary(),
            SubjectMapper::fromNullableEntity($complaintJudgement->getSubject()),
            DepartmentMapper::fromEntity($department),
            $complaintJudgement->getPublicationDate(),
            $complaintJudgement->getStatus(),
            $mainDocumentDto,
            $noticeNotPublicDto,
            $dateFrom,
            $this->getHalLinks($complaintJudgement),
        );
    }

    public static function create(
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
        ExternalId $externalId,
        string $documentPrefix,
    ): ComplaintJudgement {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setExternalId($externalId);
        $complaintJudgement->setStatus(DossierStatus::CONCEPT);
        $complaintJudgement->setDocumentPrefix($documentPrefix);

        self::update($complaintJudgement, $complaintJudgementRequestDto, $organisation, $department, $subject);

        return $complaintJudgement;
    }

    public static function update(
        ComplaintJudgement $complaintJudgement,
        ComplaintJudgementRequestDto $complaintJudgementRequestDto,
        Organisation $organisation,
        Department $department,
        ?Subject $subject,
    ): ComplaintJudgement {
        $complaintJudgement->setDateFrom($complaintJudgementRequestDto->dossierDate);
        $complaintJudgement->setDepartments([$department]);
        $complaintJudgement->setDossierNumber($complaintJudgementRequestDto->dossierNumber);
        $complaintJudgement->setOrganisation($organisation);
        if (! $complaintJudgement->getStatus()->isPublished()) {
            $complaintJudgement->setPublicationDate($complaintJudgementRequestDto->publicationDate);
        }
        $complaintJudgement->setTitle($complaintJudgementRequestDto->title);
        $complaintJudgement->setSummary($complaintJudgementRequestDto->summary);
        $complaintJudgement->setSubject($subject);

        return $complaintJudgement;
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
