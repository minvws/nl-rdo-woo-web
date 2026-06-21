<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Department\DepartmentMapper;
use PublicationApi\Api\Dossier\ComplaintJudgement\Uploads\MainDocument\ComplaintJudgementUploadMainDocumentResource;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\Organisation\OrganisationMapper;
use PublicationApi\Api\Subject\SubjectMapper;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;
use Webmozart\Assert\Assert;

use function array_map;
use function array_values;

readonly class ComplaintJudgementMapper
{
    public function __construct(
        private DossierPathHelper $dossierPathHelper,
        private MainDocumentResponseDtoFactory $mainDocumentResponseDtoFactory,
        private PublicUrlGenerator $publicUrlGenerator,
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
        Assert::notNull($mainDocument);

        $dateFrom = $complaintJudgement->getDateFrom();
        Assert::notNull($dateFrom);

        $department = $complaintJudgement->getDepartments()->first();
        Assert::isInstanceOf($department, Department::class);

        return new ComplaintJudgementResponseDto(
            $complaintJudgement->getId(),
            $complaintJudgement->getExternalId(),
            OrganisationMapper::fromEntity($complaintJudgement->getOrganisation()),
            $complaintJudgement->getDossierNr(),
            $complaintJudgement->getTitle(),
            $complaintJudgement->getSummary(),
            SubjectMapper::fromNullableEntity($complaintJudgement->getSubject()),
            DepartmentMapper::fromEntity($department),
            $complaintJudgement->getPublicationDate(),
            $complaintJudgement->getStatus(),
            $this->mainDocumentResponseDtoFactory->fromEntity(
                $mainDocument,
                ComplaintJudgementUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                ComplaintJudgementMainDocumentResponseDto::class,
            ),
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
        $complaintJudgement->setStatus(DossierStatus::NEW);
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
        $complaintJudgement->setDossierNr($complaintJudgementRequestDto->dossierNumber);
        $complaintJudgement->setOrganisation($organisation);
        $complaintJudgement->setPublicationDate($complaintJudgementRequestDto->publicationDate);
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
            new Link($this->publicUrlGenerator->buildUrlFromRoute(ComplaintJudgementResource::ROUTE_NAME_GET_COMPLAINT_JUDGEMENT, [
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
